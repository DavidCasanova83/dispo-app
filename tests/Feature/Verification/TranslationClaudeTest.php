<?php

use App\Livewire\Verification\Admin\TranslationsManager;
use App\Models\TranslationClaudeRequest;
use App\Models\User;
use App\Models\VerificationPage;
use App\Services\TranslationCheckService;
use App\Services\TranslationClaudeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const GH_API = 'https://api.github.com/repos/DavidCasanova83/VT-custom-2025';

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    config([
        'services.github_claude.token' => 'github_pat_test',
        'services.github_claude.repository' => 'DavidCasanova83/VT-custom-2025',
    ]);

    $this->translator = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $this->translator->assignRole('Traducteur');

    $this->admin = User::factory()->create(['approved' => true, 'approved_at' => now(), 'name' => 'Admin Verdon']);
    $this->admin->assignRole('Super-admin');

    $this->checks = app(TranslationCheckService::class);
    $this->claude = app(TranslationClaudeService::class);

    $this->page = VerificationPage::create([
        'title' => 'Les sommets',
        'url' => 'https://www.verdontourisme.com/les-sommets/',
        'url_en' => 'https://www.verdontourisme.com/en/the-summits/',
        'url_it' => 'https://www.verdontourisme.com/it/i-vertici/',
        'is_in_sitemap' => true,
        'last_seen_in_sitemap_at' => now(),
        'created_by' => $this->admin->id,
    ]);
});

/** Demande envoyée sur une traduction EN signalée, issue #42. */
function sentClaudeRequest($test): TranslationClaudeRequest
{
    Http::fake([GH_API.'/issues' => Http::response(['number' => 42, 'html_url' => 'https://github.com/x/issues/42'], 201)]);
    $check = $test->checks->submit($test->page, $test->translator, 'en', false, 'Le titre « Les sommets » est resté en français.');

    return $test->claude->send($check, $test->admin, 'Garder les noms des sommets.');
}

// ─── ENVOI ────────────────────────────────────────────────────────

test('la demande crée une issue @claude avec le signalement du traducteur', function () {
    $request = sentClaudeRequest($this);

    Http::assertSent(function (Request $r) {
        $body = $r['body'] ?? '';

        return $r->method() === 'POST' && $r->url() === GH_API.'/issues'
            && $r->hasHeader('Authorization', 'Bearer github_pat_test')
            && str_starts_with($r['title'], '[Traduction EN] Les sommets')
            && str_starts_with($body, '@claude')
            && str_contains($body, '> Le titre « Les sommets » est resté en français.')
            && str_contains($body, 'Garder les noms des sommets.')
            && str_contains($body, '`/en/the-summits/`')
            && str_contains($body, 'contenu.en.md')
            && str_contains($body, 'themes/verdon-site/docs/traductions-claude.md')
            && str_contains($body, 'VERDON-TRADUCTION-CLAUDE demande=tr-');
    });

    expect($request->status)->toBe('sent')
        ->and($request->issue_number)->toBe(42)
        ->and($request->reference)->toStartWith("tr-{$this->page->id}-en-")
        ->and($request->translator_comment)->toBe('Le titre « Les sommets » est resté en français.')
        // Prise en charge : le traducteur voit « en cours ».
        ->and($this->page->fresh()->translationCheckFor('en')->status)->toBe('in_progress');
});

test('seule une traduction à corriger peut être envoyée, une fois à la fois', function () {
    Http::fake([GH_API.'/issues' => Http::response(['number' => 1, 'html_url' => 'u'], 201)]);

    $correct = $this->checks->submit($this->page, $this->translator, 'it', true);
    expect(fn () => $this->claude->send($correct, $this->admin))->toThrow(DomainException::class);

    $toFix = $this->checks->submit($this->page, $this->translator, 'en', false, 'Erreur');
    $this->claude->send($toFix, $this->admin);
    expect(fn () => $this->claude->send($toFix->fresh(), $this->admin))
        ->toThrow(DomainException::class, 'déjà en cours');

    Http::assertSentCount(1);
});

test('sans jeton GitHub, rien n\'est envoyé', function () {
    config(['services.github_claude.token' => null]);
    Http::fake();
    $check = $this->checks->submit($this->page, $this->translator, 'en', false, 'Erreur');

    expect(fn () => $this->claude->send($check, $this->admin))->toThrow(DomainException::class, 'CLAUDE_GITHUB_TOKEN');
    expect(TranslationClaudeRequest::count())->toBe(0);
    Http::assertNothingSent();
});

test('une erreur GitHub est expliquée et rien n\'est enregistré', function () {
    Http::fake([GH_API.'/issues' => Http::response(['message' => 'Bad credentials'], 401)]);
    $check = $this->checks->submit($this->page, $this->translator, 'en', false, 'Erreur');

    expect(fn () => $this->claude->send($check, $this->admin))
        ->toThrow(DomainException::class, 'GitHub a répondu 401 : jeton GitHub refusé');
    expect(TranslationClaudeRequest::count())->toBe(0)
        ->and($check->fresh()->status)->toBe('to_fix');
});

// ─── SUIVI ────────────────────────────────────────────────────────

test('quand Claude a fini, le panel crée la PR et ferme l\'issue', function () {
    $request = sentClaudeRequest($this);
    $branch = 'claude/issue-42-20261022-1200';

    Http::fake([
        GH_API.'/issues/42/comments?per_page=100' => Http::response([
            ['body' => "Corrections faites.\n\nVERDON-TRADUCTION-CLAUDE demande={$request->reference} branche={$branch}"],
        ]),
        GH_API => Http::response(['default_branch' => 'master']),
        GH_API.'/compare/master...claude/issue-42-20261022-1200' => Http::response(['ahead_by' => 1]),
        GH_API.'/pulls' => Http::response(['number' => 43, 'html_url' => 'https://github.com/x/pull/43'], 201),
        GH_API.'/issues/42/comments' => Http::response([], 201),
        GH_API.'/issues/42' => Http::response([]),
    ]);

    $this->claude->sync();

    $request->refresh();
    expect($request->status)->toBe('pr_open')
        ->and($request->branch)->toBe($branch)
        ->and($request->pr_number)->toBe(43)
        ->and($request->pr_url)->toBe('https://github.com/x/pull/43');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === GH_API.'/pulls'
        && $r['head'] === $branch && $r['base'] === 'master'
        && str_contains($r['body'], '#42') && str_contains($r['body'], 'Marquer corrigée'));
    Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r->url() === GH_API.'/issues/42' && $r['state'] === 'closed');
    // Le commentaire de clôture ne relance pas Claude.
    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === GH_API.'/issues/42/comments'
        && str_contains($r['body'], '@claude'));
});

test('Claude encore au travail : la demande reste suivie, puis échoue après le délai', function () {
    $request = sentClaudeRequest($this);
    Http::fake([GH_API.'/issues/42/comments?per_page=100' => Http::response([['body' => 'Claude Code is working…']])]);

    $this->claude->sync();
    expect($request->fresh()->status)->toBe('sent');

    $request->update(['sent_at' => now()->subHours(TranslationClaudeService::TIMEOUT_HOURS + 1)]);
    $this->claude->sync();
    expect($request->fresh()->status)->toBe('failed')
        ->and($request->fresh()->error)->toContain('3 heures');
});

test('branche=aucune : pas de PR, l\'issue est fermée', function () {
    $request = sentClaudeRequest($this);
    Http::fake([
        GH_API.'/issues/42/comments?per_page=100' => Http::response([
            ['body' => "Page de l'admin : texte à coller…\nVERDON-TRADUCTION-CLAUDE demande={$request->reference} branche=aucune"],
        ]),
        GH_API => Http::response(['default_branch' => 'master']),
        GH_API.'/issues/42/comments' => Http::response([], 201),
        GH_API.'/issues/42' => Http::response([]),
    ]);

    $this->claude->sync();

    expect($request->fresh()->status)->toBe('no_change');
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/pulls'));
});

test('la ligne repère d\'une autre demande est ignorée', function () {
    $request = sentClaudeRequest($this);
    Http::fake([GH_API.'/issues/42/comments?per_page=100' => Http::response([
        ['body' => 'VERDON-TRADUCTION-CLAUDE demande=tr-999-en-20260101000000 branche=claude/autre'],
    ])]);

    $this->claude->sync();

    expect($request->fresh()->status)->toBe('sent');
});

test('le suivi passe la PR fusionnée ou fermée', function () {
    $request = sentClaudeRequest($this);
    $request->update(['status' => 'pr_open', 'pr_number' => 43]);

    Http::fake([GH_API.'/pulls/43' => Http::sequence()
        ->push(['state' => 'open', 'merged_at' => null])
        ->push(['state' => 'closed', 'merged_at' => '2026-10-22T12:00:00Z'])]);

    $this->claude->sync();
    expect($request->fresh()->status)->toBe('pr_open');

    $this->claude->sync();
    expect($request->fresh()->status)->toBe('merged')
        // « Marquer corrigée » reste à l'admin, après déploiement.
        ->and($this->page->fresh()->translationCheckFor('en')->status)->toBe('in_progress');
});

test('une erreur passagère de GitHub laisse la demande en cours', function () {
    $request = sentClaudeRequest($this);
    Http::fake([GH_API.'/issues/42/comments?per_page=100' => Http::response(['message' => 'Server Error'], 502)]);

    $this->claude->sync();

    expect($request->fresh()->status)->toBe('sent')
        ->and($request->fresh()->error)->toContain('502');
});

test('la commande de suivi n\'appelle pas GitHub sans demande en cours', function () {
    Http::fake();

    $this->artisan('translations:claude-sync')->expectsOutput('Aucune demande en cours.')->assertSuccessful();

    Http::assertNothingSent();
});

// ─── ÉCRAN ADMIN ──────────────────────────────────────────────────

test('le super-admin envoie la correction à Claude depuis la fiche', function () {
    Http::fake([GH_API.'/issues' => Http::response(['number' => 7, 'html_url' => 'https://github.com/x/issues/7'], 201)]);
    $this->checks->submit($this->page, $this->translator, 'it', false, 'Frase in francese');

    Livewire::actingAs($this->admin)
        ->test(TranslationsManager::class)
        ->call('openPage', $this->page->id)
        ->assertSee('Demander la correction à Claude')
        ->set('claudeMessages.it', 'Ton plus formel')
        ->call('askClaude', 'it')
        ->assertHasNoErrors()
        ->assertSee('issue #7')
        ->assertDontSee('Demander la correction à Claude')
        // Liste : la demande en cours est signalée sur la ligne.
        ->call('closePage')
        ->assertSee('🤖 Claude : Chez Claude');

    $request = TranslationClaudeRequest::first();
    expect($request->language)->toBe('it')
        ->and($request->message)->toBe('Ton plus formel')
        ->and($request->requested_by)->toBe($this->admin->id);
});

test('l\'erreur d\'envoi s\'affiche dans la fiche', function () {
    Http::fake([GH_API.'/issues' => Http::response(['message' => 'Not Found'], 404)]);
    $this->checks->submit($this->page, $this->translator, 'en', false, 'Erreur');

    Livewire::actingAs($this->admin)
        ->test(TranslationsManager::class)
        ->call('openPage', $this->page->id)
        ->call('askClaude', 'en')
        ->assertHasErrors(['claude.en']);
});
