<?php

use App\Livewire\Verification\Admin\PageSheet;
use App\Livewire\Verification\Admin\ToHandle;
use App\Models\User;
use App\Models\VerificationClaudeRequest;
use App\Models\VerificationEvent;
use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\PageReleaseService;
use App\Services\VerificationClaudeService;
use App\Services\VerificationPageService;
use App\Services\VerificationReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);

    config([
        'services.github_claude.token' => 'github_pat_test',
        'services.github_claude.repository' => 'DavidCasanova83/VT-custom-2025',
    ]);
    $this->api = 'https://api.github.com/repos/DavidCasanova83/VT-custom-2025';

    $this->admin = User::factory()->create(['approved' => true, 'approved_at' => now(), 'name' => 'Admin Verdon']);
    $this->admin->assignRole('Super-admin');

    $this->reviewer = User::factory()->create(['approved' => true, 'approved_at' => now(), 'name' => 'Marie Relectrice']);
    $this->reviewer->assignRole('Utilisateurs');

    $this->claude = app(VerificationClaudeService::class);

    $this->page = VerificationPage::create([
        'title' => 'Marchés provençaux',
        'url' => 'https://www.verdontourisme.com/marches-provencaux/',
        'url_en' => 'https://www.verdontourisme.com/en/provencal-markets/',
        'is_in_sitemap' => true,
        'last_seen_in_sitemap_at' => now(),
        'created_by' => $this->admin->id,
    ]);
    app(VerificationPageService::class)->addAssignees([$this->page->id], [$this->reviewer->id]);
    app(PageReleaseService::class)->releasePageNow($this->page->id);

    $this->review = app(VerificationReviewService::class)->submit($this->page, $this->reviewer, [
        'language' => 'fr',
        'content_ok' => false,
        'content_comment' => 'Le marché de Riez a lieu le mercredi et le samedi.',
        'media_ok' => false,
        'media_comment' => 'Le lien vers la navette est cassé.',
        'suggestions' => 'Ajouter le marché nocturne de Moustiers.',
    ]);
});

/** Demande envoyée, issue #42. */
function sentPageClaudeRequest($test): VerificationClaudeRequest
{
    Http::fake([$test->api.'/issues' => Http::response(['number' => 42, 'html_url' => 'https://github.com/x/issues/42'], 201)]);

    return $test->claude->send($test->page, $test->admin, [$test->review->id], 'Ne pas toucher aux horaires.');
}

// ─── ENVOI ────────────────────────────────────────────────────────

test('la demande crée une issue @claude avec les retours, sans le nom des relecteurs', function () {
    $request = sentPageClaudeRequest($this);
    $api = $this->api;

    Http::assertSent(function (Request $r) use ($api) {
        $body = $r['body'] ?? '';

        return $r->method() === 'POST' && $r->url() === $api.'/issues'
            && str_starts_with($r['title'], '[Vérification] Marchés provençaux')
            && str_starts_with($body, '@claude')
            && str_contains($body, '> Le marché de Riez a lieu le mercredi et le samedi.')
            && str_contains($body, '> Le lien vers la navette est cassé.')
            && str_contains($body, 'Ajouter le marché nocturne de Moustiers.')
            && str_contains($body, 'Ne pas toucher aux horaires.')
            && str_contains($body, '`/marches-provencaux/`')
            && str_contains($body, 'themes/verdon-site/docs/verification-claude.md')
            && str_contains($body, 'VERDON-VERIFICATION-CLAUDE demande=vp-')
            && ! str_contains($body, 'Marie Relectrice');
    });

    expect($request->status)->toBe('sent')
        ->and($request->issue_number)->toBe(42)
        ->and($request->review_ids)->toBe([$this->review->id])
        ->and($request->reference)->toStartWith("vp-{$this->page->id}-c1-")
        // Prise en charge : le relecteur voit « En cours de correction ».
        ->and($this->review->fresh()->status)->toBe('in_progress')
        ->and($this->page->fresh()->status)->toBe('in_progress')
        ->and(VerificationEvent::where('page_id', $this->page->id)->where('type', 'claude_requested')->exists())->toBeTrue();
});

test('seuls les retours ouverts partent, une demande à la fois', function () {
    Http::fake([$this->api.'/issues' => Http::response(['number' => 1, 'html_url' => 'u'], 201)]);

    expect(fn () => $this->claude->send($this->page, $this->admin, []))
        ->toThrow(DomainException::class, 'au moins un retour');

    $this->claude->send($this->page, $this->admin, [$this->review->id]);
    expect(fn () => $this->claude->send($this->page, $this->admin, [$this->review->id]))
        ->toThrow(DomainException::class, 'déjà en cours');

    Http::assertSentCount(1);
});

test('sans jeton ou en cas d\'erreur GitHub, rien n\'est enregistré', function () {
    Http::fake([$this->api.'/issues' => Http::response(['message' => 'Bad credentials'], 401)]);

    expect(fn () => $this->claude->send($this->page, $this->admin, [$this->review->id]))
        ->toThrow(DomainException::class, 'GitHub a répondu 401');

    config(['services.github_claude.token' => null]);
    expect(fn () => $this->claude->send($this->page, $this->admin, [$this->review->id]))
        ->toThrow(DomainException::class, 'CLAUDE_GITHUB_TOKEN');

    expect(VerificationClaudeRequest::count())->toBe(0)
        ->and($this->review->fresh()->status)->toBe('pending_admin');
});

// ─── SUIVI ────────────────────────────────────────────────────────

test('quand Claude a fini, le panel crée la PR, ferme l\'issue et l\'inscrit au journal', function () {
    $request = sentPageClaudeRequest($this);
    $branch = 'claude/issue-42-20261027-1200';

    Http::fake([
        $this->api.'/issues/42/comments?per_page=100' => Http::response([
            ['body' => "Corrections faites.\n\nVERDON-VERIFICATION-CLAUDE demande={$request->reference} branche={$branch}"],
        ]),
        $this->api => Http::response(['default_branch' => 'main']),
        $this->api.'/compare/main...claude/issue-42-20261027-1200' => Http::response(['ahead_by' => 2]),
        $this->api.'/pulls' => Http::response(['number' => 43, 'html_url' => 'https://github.com/x/pull/43'], 201),
        $this->api.'/issues/42/comments' => Http::response([], 201),
        $this->api.'/issues/42' => Http::response([]),
    ]);

    $this->artisan('verification:claude-sync')->assertSuccessful();

    $request->refresh();
    expect($request->status)->toBe('pr_open')
        ->and($request->pr_number)->toBe(43)
        ->and(VerificationEvent::where('type', 'claude_updated')->first()->describe())->toContain('PR #43 ouverte');

    $api = $this->api;
    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === $api.'/pulls'
        && $r['head'] === $branch && $r['base'] === 'main'
        && str_starts_with($r['title'], 'Vérification — Marchés provençaux')
        && str_contains($r['body'], '#42') && str_contains($r['body'], 'Le marché de Riez')
        && ! str_contains($r['body'], 'Marie Relectrice'));
    Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r->url() === $api.'/issues/42' && $r['state'] === 'closed');
});

test('branche=aucune : pas de PR ; une PR fusionnée laisse les retours à l\'admin', function () {
    $request = sentPageClaudeRequest($this);
    Http::fake([
        $this->api.'/issues/42/comments?per_page=100' => Http::response([
            ['body' => "Page de l'admin WordPress : marche à suivre…\nVERDON-VERIFICATION-CLAUDE demande={$request->reference} branche=aucune"],
        ]),
        $this->api => Http::response(['default_branch' => 'main']),
        $this->api.'/issues/42/comments' => Http::response([], 201),
        $this->api.'/issues/42' => Http::response([]),
    ]);

    $this->claude->sync();
    expect($request->fresh()->status)->toBe('no_change');
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/pulls'));

    $request->update(['status' => 'pr_open', 'pr_number' => 43]);
    Http::fake([$this->api.'/pulls/43' => Http::response(['state' => 'closed', 'merged_at' => '2026-10-27T12:00:00Z'])]);
    $this->claude->sync();

    expect($request->fresh()->status)->toBe('merged')
        // Marquer les retours « Réglé » reste à l'admin, après déploiement.
        ->and($this->review->fresh()->status)->toBe('in_progress');
});

test('la commande de suivi n\'appelle pas GitHub sans demande en cours', function () {
    Http::fake();

    $this->artisan('verification:claude-sync')->expectsOutput('Aucune demande en cours.')->assertSuccessful();

    Http::assertNothingSent();
});

// ─── ÉCRAN : RÉSERVÉ AU SUPER-ADMIN ──────────────────────────────

test('le super-admin envoie la correction depuis la fiche', function () {
    Http::fake([$this->api.'/issues' => Http::response(['number' => 7, 'html_url' => 'https://github.com/x/issues/7'], 201)]);

    Livewire::actingAs($this->admin)
        ->test(PageSheet::class, ['page' => $this->page->id])
        ->assertSee('Correction par Claude')
        ->assertSet('claudeReviewIds', [(string) $this->review->id])
        ->set('claudeMessage', 'Garder le ton actuel.')
        ->call('askClaude')
        ->assertHasNoErrors()
        ->assertSee('issue #7')
        ->assertDontSee('Demander la correction à Claude');

    $request = VerificationClaudeRequest::first();
    expect($request->message)->toBe('Garder le ton actuel.')
        ->and($request->requested_by)->toBe($this->admin->id);

    Livewire::actingAs($this->admin)->test(ToHandle::class)->assertSee('🤖 Claude : Chez Claude');
});

test('un autre rôle ayant la vérification des pages ne voit pas Claude', function () {
    $manager = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $manager->assignRole('Admin');
    $manager->givePermissionTo('manage-page-verification');

    Livewire::actingAs($manager)
        ->test(PageSheet::class, ['page' => $this->page->id])
        ->assertOk()
        ->assertDontSee('Correction par Claude')
        ->assertSet('claudeReviewIds', [])
        ->call('askClaude')
        ->assertForbidden();

    expect(VerificationClaudeRequest::count())->toBe(0);
});

test('l\'erreur d\'envoi s\'affiche dans la fiche', function () {
    Http::fake([$this->api.'/issues' => Http::response(['message' => 'Not Found'], 404)]);

    Livewire::actingAs($this->admin)
        ->test(PageSheet::class, ['page' => $this->page->id])
        ->call('askClaude')
        ->assertHasErrors(['claude']);

    expect(VerificationReview::find($this->review->id)->status)->toBe('pending_admin');
});
