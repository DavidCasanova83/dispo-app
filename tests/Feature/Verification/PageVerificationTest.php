<?php

use App\Livewire\Verification\Admin\PageSheet;
use App\Livewire\Verification\Admin\PagesManager;
use App\Livewire\Verification\Admin\ToHandle;
use App\Livewire\Verification\Admin\Trash;
use App\Livewire\Verification\MyVerificationsHistory;
use App\Livewire\Verification\VerificationForm;
use App\Models\User;
use App\Models\VerificationEvent;
use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\PageReleaseService;
use App\Services\VerificationOverviewService;
use App\Services\VerificationPageService;
use App\Services\VerificationReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $this->admin->assignRole('Super-admin');

    $this->reviewer = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $this->reviewer->assignRole('Utilisateurs');

    $this->reviews = app(VerificationReviewService::class);
    $this->pages = app(VerificationPageService::class);
});

function vpPage(array $attributes = []): VerificationPage
{
    static $n = 0;
    $n++;

    return VerificationPage::create(array_merge([
        'title' => "Page vérifiée {$n}",
        'url' => "https://www.verdontourisme.com/verif-{$n}/",
        'is_in_sitemap' => true,
        'last_seen_in_sitemap_at' => now(),
        'created_by' => User::factory()->create()->id,
    ], $attributes));
}

/** Page assignée et distribuée au relecteur. */
function vpAssigned(User $reviewer, array $attributes = []): VerificationPage
{
    $page = vpPage($attributes);
    app(VerificationPageService::class)->addAssignees([$page->id], [$reviewer->id]);
    app(PageReleaseService::class)->releasePageNow($page->id);

    return $page->fresh();
}

function vpSubmit(VerificationPage $page, User $user, bool $ok = true, string $comment = 'Le marché a changé de jour.'): VerificationReview
{
    return app(VerificationReviewService::class)->submit($page, $user, [
        'language' => 'fr',
        'content_ok' => $ok,
        'content_comment' => $ok ? '' : $comment,
        'media_ok' => true,
    ]);
}

// ─── ACCÈS ────────────────────────────────────────────────────────

test('l\'admin accède aux onglets et à la fiche, le relecteur non', function () {
    $page = vpPage();

    foreach (['verification.admin.todo', 'verification.admin.pages', 'verification.admin.verified', 'verification.admin.trash'] as $route) {
        $this->actingAs($this->admin)->get(route($route))->assertOk();
        $this->actingAs($this->reviewer)->get(route($route))->assertForbidden();
    }

    $this->actingAs($this->admin)->get(route('verification.admin.page', $page->id))->assertOk();
    $this->actingAs($this->reviewer)->get(route('verification.admin.page', $page->id))->assertForbidden();
});

test('tableau de bord et menu : pastilles admin et relecteur', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer, false);

    $this->actingAs($this->admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Vérification des pages')
        ->assertSee('1 nouveau(x) retour(s)');

    $this->actingAs($this->admin);
    $this->reviews->resolve($review, 'revision_requested', 'À revoir svp');

    $this->actingAs($this->reviewer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mes vérifications')
        ->assertSee('1 réponse(s) de l\'équipe', false);

    $this->actingAs($this->reviewer)->get(route('verification.index'))->assertOk()->assertSee('Relire la page');
    $this->actingAs($this->reviewer)->get(route('verification.history'))->assertOk()->assertSee('Nouveau');
    $this->actingAs($this->reviewer)->get(route('verification.form', $page))->assertOk()->assertSee('À revoir svp');
});

test('l\'ancienne boîte de retours redirige vers À traiter ou vers la fiche', function () {
    $page = vpPage();

    $this->actingAs($this->admin)->get('/verification/admin/retours')->assertRedirect(route('verification.admin.todo'));
    $this->actingAs($this->admin)->get('/verification/admin/retours?page='.$page->id)->assertRedirect(route('verification.admin.page', $page->id));
});

// ─── CLÔTURE : ARCHIVER, NE RIEN SUPPRIMER ───────────────────────

test('la clôture archive les relectures au lieu de les supprimer', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer);

    expect($page->fresh()->status)->toBe('awaiting_validation');

    $archived = $this->reviews->closeAnnualVerification($page);

    $page->refresh();
    expect($archived)->toBe(1)
        ->and($page->status)->toBe('validated')
        ->and($page->cycle)->toBe(2)
        ->and($page->reviews()->count())->toBe(0)
        ->and($page->allReviews()->count())->toBe(1)
        ->and($review->fresh()->archived_at)->not->toBeNull()
        ->and(VerificationEvent::where('page_id', $page->id)->where('type', 'closed')->exists())->toBeTrue();

    // L'historique du relecteur garde la relecture.
    expect($this->reviews->historyForUser($this->reviewer)->pluck('id')->all())->toBe([$review->id]);

    $this->actingAs($this->admin)->get(route('verification.admin.verified'))->assertOk()->assertSee($page->title);
    $this->actingAs($this->reviewer)->get(route('verification.history'))->assertOk()->assertSee('Cycle clôturé');
});

test('le cycle suivant crée de nouvelles relectures à côté des archivées', function () {
    $page = vpAssigned($this->reviewer);
    vpSubmit($page, $this->reviewer);
    $this->reviews->closeAnnualVerification($page);

    $this->travel(VerificationPage::REVALIDATION_DAYS + 1)->days();
    $this->artisan('verification:revalidate-aged')->assertSuccessful();
    app(PageReleaseService::class)->releasePageNow($page->id);
    $page->refresh();

    expect($page->status)->toBe('pending')
        ->and(VerificationPage::pendingForUser($this->reviewer)->pluck('id')->all())->toBe([$page->id]);

    $second = vpSubmit($page, $this->reviewer, false);

    expect($second->cycle)->toBe(2)
        ->and($page->allReviews()->count())->toBe(2)
        ->and($page->reviews()->pluck('id')->all())->toBe([$second->id])
        ->and($page->fresh()->status)->toBe('needs_fix')
        ->and(VerificationEvent::where('page_id', $page->id)->where('type', 'renewed')->exists())->toBeTrue();
});

test('la clôture en masse ne clôt que les pages prêtes', function () {
    $ready = vpAssigned($this->reviewer);
    vpSubmit($ready, $this->reviewer);
    $notReady = vpAssigned($this->reviewer);

    $result = $this->reviews->closeMany([$ready->id, $notReady->id]);

    expect($result)->toMatchArray(['closed' => 1, 'skipped' => 1, 'archived' => 1])
        ->and($ready->fresh()->status)->toBe('validated')
        ->and($notReady->fresh()->status)->toBe('pending');
});

// ─── JOURNAL : RIEN N'EST ÉCRASÉ SANS TRACE ──────────────────────

test('une relecture renvoyée puis resoumise garde sa version précédente au journal', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer, false, 'La date du marché de Riez est fausse.');

    $this->actingAs($this->admin);
    $this->reviews->resolve($review, 'revision_requested', 'Pouvez-vous vérifier la date exacte ?');

    vpSubmit($page, $this->reviewer, true);

    $event = VerificationEvent::where('page_id', $page->id)
        ->where('type', 'review_submitted')
        ->latest('id')
        ->first();

    expect($event->data['resubmitted'])->toBeTrue()
        ->and($event->data['previous']['content_comment'])->toBe('La date du marché de Riez est fausse.')
        ->and($event->data['previous']['admin_response'])->toBe('Pouvez-vous vérifier la date exacte ?')
        ->and(VerificationEvent::where('page_id', $page->id)->where('type', 'review_resolved')->first()->data['message'])
        ->toBe('Pouvez-vous vérifier la date exacte ?');
});

test('retirer un relecteur garde ses relectures et note l\'assignation au journal', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer);

    $this->pages->removeAssignees([$page->id], [$this->reviewer->id]);

    $event = VerificationEvent::where('page_id', $page->id)->where('type', 'unassigned')->first();

    expect($review->fresh())->not->toBeNull()
        ->and($page->assignees()->count())->toBe(0)
        ->and($event->user_id)->toBe($this->reviewer->id)
        ->and($event->data['released_at'])->not->toBeNull()
        ->and($page->fresh()->status)->toBe('pending');
});

test('supprimer son compte ne supprime plus ses relectures', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer);

    $this->reviewer->delete();

    expect($review->fresh())->not->toBeNull()
        ->and($review->fresh()->user_id)->toBeNull();
});

// ─── CORBEILLE ───────────────────────────────────────────────────

test('les pages qui ne sont plus sur le site partent en corbeille et se restaurent', function () {
    $gone = vpPage(['is_in_sitemap' => false]);
    $kept = vpPage();
    DB::table('verification_assignments')->insert(['page_id' => $gone->id, 'user_id' => $this->reviewer->id, 'released_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    $review = vpSubmit($gone, $this->reviewer);

    Livewire::actingAs($this->admin)
        ->test(PagesManager::class)
        ->call('askRemoveOutOfSite')
        ->assertSet('removingIds', [$gone->id])
        ->call('confirmRemove');

    expect($gone->fresh()->trashed())->toBeTrue()
        ->and($kept->fresh()->trashed())->toBeFalse()
        ->and($review->fresh())->not->toBeNull();

    Livewire::actingAs($this->admin)->test(Trash::class)->call('restore', $gone->id);

    expect($gone->fresh()->trashed())->toBeFalse()
        ->and(VerificationEvent::where('page_id', $gone->id)->pluck('type')->all())->toContain('removed', 'restored');
});

// ─── ÉTAPES ──────────────────────────────────────────────────────

test('les compteurs du bandeau correspondent aux pages filtrées par étape', function () {
    $other = User::factory()->create(['approved' => true]);

    vpPage();                                                    // sans relecteur
    $queued = vpPage();
    $this->pages->addAssignees([$queued->id], [$this->reviewer->id]); // en attente
    vpAssigned($this->reviewer);                                 // en relecture
    $toHandle = vpAssigned($this->reviewer);
    vpSubmit($toHandle, $this->reviewer, false);                 // retours à traiter
    $ready = vpAssigned($other);
    vpSubmit($ready, $other);                                    // prête à clôturer
    $verified = vpAssigned($other);
    vpSubmit($verified, $other);
    $this->reviews->closeAnnualVerification($verified);         // vérifiée

    $counts = app(VerificationOverviewService::class)->stageCounts();

    foreach (VerificationPage::STAGES as $stage => $def) {
        expect(VerificationPage::inStage($stage)->count())->toBe($counts[$stage], "étape {$stage}");
    }

    expect($counts)->toMatchArray([
        'unassigned' => 1, 'queued' => 1, 'reviewing' => 1, 'to_handle' => 1,
        'correcting' => 0, 'ready' => 1, 'verified' => 1,
    ]);

    VerificationPage::with(['assignees', 'reviews'])->get()->each(
        fn (VerificationPage $page) => expect(VerificationPage::inStage($page->stage())->whereKey($page->id)->exists())->toBeTrue()
    );
});

// ─── DISTRIBUTION ────────────────────────────────────────────────

test('le plafond de pages ouvertes se règle, 2 par défaut', function () {
    expect(PageReleaseService::quota())->toBe(2);

    $pages = collect(range(1, 4))->map(fn () => vpPage());
    $this->pages->addAssignees($pages->pluck('id')->all(), [$this->reviewer->id]);

    PageReleaseService::setQuota(3);
    $released = app(PageReleaseService::class)->releaseForUserUpToQuota($this->reviewer->id);

    expect($released)->toBe(3)
        ->and(VerificationEvent::where('type', 'distributed')->count())->toBe(3);

    Livewire::actingAs($this->admin)
        ->test(PagesManager::class)
        ->call('openSettings')
        ->assertSet('quota', 3)
        ->set('quota', 50)
        ->call('saveSettings')
        ->assertHasErrors('quota');
});

// ─── TRAITEMENT DES RETOURS ──────────────────────────────────────

test('À traiter : un retour se règle en un clic, avec une réponse', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer, false);

    Livewire::actingAs($this->admin)
        ->test(ToHandle::class)
        ->assertSee($page->title)
        ->call('startReply', $review->id)
        ->set("replies.{$review->id}", 'Corrigé, merci !')
        ->call('resolveReview', $review->id, 'done');

    $review->refresh();
    expect($review->status)->toBe('done')
        ->and($review->admin_response)->toBe('Corrigé, merci !')
        ->and($review->reviewer_seen_at)->toBeNull()
        ->and($page->fresh()->status)->toBe('awaiting_validation');
});

test('À traiter : la clôture en masse passe par une confirmation', function () {
    $page = vpAssigned($this->reviewer);
    vpSubmit($page, $this->reviewer);

    Livewire::actingAs($this->admin)
        ->test(ToHandle::class)
        ->call('selectAllReady')
        ->call('askClose')
        ->assertSet('showCloseModal', true)
        ->assertSee('Rien n\'est supprimé', false)
        ->call('confirmClose');

    expect($page->fresh()->status)->toBe('validated')
        ->and($page->allReviews()->count())->toBe(1);
});

test('la fiche montre les relecteurs sans réponse et les cycles archivés', function () {
    $other = User::factory()->create(['approved' => true, 'name' => 'Léa Martin']);
    $page = vpAssigned($this->reviewer);
    vpSubmit($page, $this->reviewer);
    $this->pages->addAssignees([$page->id], [$other->id]);

    Livewire::actingAs($this->admin)
        ->test(PageSheet::class, ['page' => $page->id])
        ->assertSee('Léa Martin')
        ->assertSee('en attente de distribution');

    $this->pages->removeAssignees([$page->id], [$other->id]);
    $this->reviews->closeAnnualVerification($page->fresh());

    Livewire::actingAs($this->admin)
        ->test(PageSheet::class, ['page' => $page->id])
        ->assertSee('Cycle 1')
        ->call('$set', 'openCycle', 1)
        ->assertSee('Rien à signaler');
});

// ─── CÔTÉ RELECTEUR ──────────────────────────────────────────────

test('la relecture se fait en français uniquement', function () {
    $page = vpAssigned($this->reviewer, ['url_en' => 'https://www.verdontourisme.com/en/x/']);

    Livewire::withQueryParams(['lang' => 'en'])
        ->actingAs($this->reviewer)
        ->test(VerificationForm::class, ['page' => $page])
        ->assertSet('language', 'fr');
});

test('une réponse de l\'admin est nouvelle jusqu\'à ce que le relecteur la voie', function () {
    $page = vpAssigned($this->reviewer);
    $review = vpSubmit($page, $this->reviewer, false);

    expect($this->reviews->unseenResponsesCount($this->reviewer))->toBe(0);

    $this->actingAs($this->admin);
    $this->reviews->resolve($review, 'in_progress', 'Je m\'en occupe.');

    expect($this->reviews->unseenResponsesCount($this->reviewer))->toBe(1);
    $handledAt = $review->fresh()->updated_at;
    $this->travel(2)->days();

    Livewire::actingAs($this->reviewer)
        ->test(MyVerificationsHistory::class)
        ->assertSet('newIds', [$review->id])
        ->assertSee('Nouveau');

    // « Vu » ne change ni la date de traitement ni la date de soumission.
    expect($this->reviews->unseenResponsesCount($this->reviewer))->toBe(0)
        ->and($review->fresh()->updated_at->equalTo($handledAt))->toBeTrue()
        ->and($review->fresh()->submittedAt()->equalTo($review->submitted_at))->toBeTrue();
});

// ─── ACTIONS EN MASSE (ONGLET PAGES) ─────────────────────────────

test('Pages : actions en masse sur la sélection', function () {
    $other = User::factory()->create(['approved' => true]);
    $a = vpAssigned($this->reviewer);
    $b = vpPage();

    $component = Livewire::actingAs($this->admin)
        ->test(PagesManager::class)
        ->assertSee($a->title)
        ->set('selectedPageIds', [(string) $a->id, (string) $b->id]);

    $component->call('openBulk', 'assign')->set('bulkUserIds', [(string) $other->id])->call('applyBulk');
    expect($b->assignees()->pluck('users.id')->all())->toBe([$other->id]);

    $component->set('selectedPageIds', [$a->id, $b->id])
        ->call('openBulk', 'deadline')->set('bulkDeadline', '2026-12-15')->call('applyBulk');
    expect($a->fresh()->deadline->toDateString())->toBe('2026-12-15');

    $component->set('selectedPageIds', [$a->id, $b->id])
        ->call('openBulk', 'category')->set('bulkCategory', 'agenda')->call('applyBulk');
    expect($b->fresh()->category)->toBe('agenda');

    $component->set('selectedPageIds', [$b->id])
        ->call('openBulk', 'release')->call('applyBulk');
    expect(DB::table('verification_assignments')->where('page_id', $b->id)->whereNull('released_at')->count())->toBe(0);

    $component->set('selectedPageIds', [$a->id, $b->id])
        ->call('openBulk', 'unassign')->set('bulkUserIds', [(string) $other->id])->call('applyBulk');
    expect($b->assignees()->count())->toBe(0);

    $component->set('selectedPageIds', [$b->id])
        ->call('openBulk', 'remove')->call('applyBulk')
        ->assertSet('showRemoveModal', true)
        ->call('confirmRemove');
    expect($b->fresh()->trashed())->toBeTrue()
        ->and($a->fresh()->trashed())->toBeFalse();
});

test('Pages : filtres par étape et ajout d\'une page avec relecteurs', function () {
    vpPage(['title' => 'Navettes estivales']);
    vpAssigned($this->reviewer, ['title' => 'Lac de Sainte-Croix']);

    Livewire::actingAs($this->admin)
        ->withQueryParams(['etape' => 'unassigned'])
        ->test(PagesManager::class)
        ->assertSee('Navettes estivales')
        ->assertDontSee('Lac de Sainte-Croix')
        ->call('applyQuickFilter', 'all')
        ->assertSee('Lac de Sainte-Croix')
        ->call('openCreateModal')
        ->set('title', 'Gorges du Verdon')
        ->set('url', 'https://www.verdontourisme.com/gorges-du-verdon/')
        ->set('assigneeIds', [(string) $this->reviewer->id])
        ->call('savePage')
        ->assertHasNoErrors();

    $page = VerificationPage::where('title', 'Gorges du Verdon')->firstOrFail();
    expect($page->assignees()->pluck('users.id')->all())->toBe([$this->reviewer->id])
        ->and(VerificationEvent::where('page_id', $page->id)->pluck('type')->all())->toContain('created', 'assigned');
});

test('les anciennes relectures EN/IT restent visibles et traitables dans la fiche', function () {
    $page = vpAssigned($this->reviewer, ['url_en' => 'https://www.verdontourisme.com/en/x/']);
    $en = app(VerificationReviewService::class)->submit($page, $this->reviewer, [
        'language' => 'en', 'content_ok' => false, 'content_comment' => 'Mauvaise traduction du titre.', 'media_ok' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(PageSheet::class, ['page' => $page->id])
        ->assertSee('Anciennes relectures EN / IT')
        ->assertSee('Mauvaise traduction du titre.')
        ->call('resolveReview', $en->id, 'done');

    expect($en->fresh()->status)->toBe('done')
        ->and($page->fresh()->status)->toBe('pending');
});
