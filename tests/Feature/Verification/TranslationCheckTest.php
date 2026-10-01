<?php

use App\Livewire\Verification\Admin\TranslationsManager;
use App\Livewire\Verification\Translation\TranslationForm;
use App\Models\TranslationCheck;
use App\Models\User;
use App\Models\VerificationPage;
use App\Services\TranslationCheckService;
use App\Services\VerificationReviewService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->translator = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $this->translator->assignRole('Traducteur');

    $this->admin = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $this->admin->assignRole('Super-admin');

    $this->service = app(TranslationCheckService::class);
});

function translationPage(array $attributes = []): VerificationPage
{
    static $n = 0;
    $n++;

    return VerificationPage::create(array_merge([
        'title' => "Page {$n}",
        'url' => "https://www.verdontourisme.com/page-{$n}/",
        'url_en' => "https://www.verdontourisme.com/en/page-{$n}/",
        'url_it' => "https://www.verdontourisme.com/it/page-{$n}/",
        'is_in_sitemap' => true,
        'last_seen_in_sitemap_at' => now(),
        'created_by' => User::factory()->create()->id,
    ], $attributes));
}

// ─── ACCÈS ────────────────────────────────────────────────────────

test('le traducteur accède à la liste et au formulaire, pas au suivi admin', function () {
    $page = translationPage();

    $this->actingAs($this->translator)->get(route('verification.translations.index'))->assertOk();
    $this->actingAs($this->translator)->get(route('verification.translations.form', $page))->assertOk();
    $this->actingAs($this->translator)->get(route('verification.admin.translations'))->assertForbidden();
});

test('le super-admin accède au suivi des traductions', function () {
    translationPage();

    $this->actingAs($this->admin)->get(route('verification.admin.translations'))->assertOk();
});

test('un utilisateur sans le rôle Traducteur n\'accède pas aux traductions', function () {
    $user = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $user->assignRole('Utilisateurs');

    $this->actingAs($user)->get(route('verification.translations.index'))->assertForbidden();
});

test('une page sortie du sitemap n\'est plus proposée', function () {
    $orphan = translationPage(['is_in_sitemap' => false]);
    $manual = translationPage(['is_in_sitemap' => false, 'last_seen_in_sitemap_at' => null]);

    expect(VerificationPage::forTranslation()->pluck('id')->all())->toBe([$manual->id]);
    $this->actingAs($this->translator)->get(route('verification.translations.form', $orphan))->assertNotFound();
});

// ─── VERDICT DU TRADUCTEUR ────────────────────────────────────────

test('une traduction correcte est validée directement', function () {
    $page = translationPage();

    $check = $this->service->submit($page, $this->translator, 'en', true);

    expect($check->status)->toBe('correct')
        ->and($check->isValidated())->toBeTrue()
        ->and($check->checked_by)->toBe($this->translator->id)
        ->and($this->service->countToHandle())->toBe(0);
});

test('une traduction à corriger exige un commentaire et remonte à l\'admin', function () {
    $page = translationPage();

    expect(fn () => $this->service->submit($page, $this->translator, 'it', false, '  '))
        ->toThrow(DomainException::class);

    $this->service->submit($page, $this->translator, 'it', false, 'Titre mal traduit');

    expect(TranslationCheck::where('page_id', $page->id)->where('language', 'it')->value('status'))->toBe('to_fix')
        ->and($this->service->countToHandle())->toBe(1);
});

test('un seul état par page et par langue', function () {
    $page = translationPage();
    $other = User::factory()->create(['approved' => true]);

    $this->service->submit($page, $this->translator, 'en', false, 'Erreur');
    $this->service->submit($page, $other, 'en', true);

    expect(TranslationCheck::where('page_id', $page->id)->count())->toBe(1)
        ->and(TranslationCheck::first()->status)->toBe('correct')
        ->and(TranslationCheck::first()->comment)->toBeNull();
});

test('le verdict exige l\'URL de la langue, que le traducteur peut renseigner', function () {
    $page = translationPage(['url_en' => null]);

    expect(fn () => $this->service->submit($page, $this->translator, 'en', true))
        ->toThrow(DomainException::class);

    $this->service->fillMissingUrl($page, 'en', 'https://www.verdontourisme.com/en/new/');
    expect($page->fresh()->url_en)->toBe('https://www.verdontourisme.com/en/new/');

    // Une URL existante ne se remplace pas depuis l'espace traducteur.
    expect(fn () => $this->service->fillMissingUrl($page, 'en', 'https://autre.example/'))
        ->toThrow(DomainException::class);

    expect($this->service->submit($page, $this->translator, 'en', true)->status)->toBe('correct');
});

test('le traducteur ne peut plus modifier une traduction prise en charge', function () {
    $page = translationPage();
    $check = $this->service->submit($page, $this->translator, 'en', false, 'Erreur');
    $this->service->resolve($check, $this->admin, 'in_progress');

    expect(fn () => $this->service->submit($page, $this->translator, 'en', true))
        ->toThrow(DomainException::class);
});

// ─── TRAITEMENT ADMIN ─────────────────────────────────────────────

test('circuit admin : à corriger, en cours, corrigée', function () {
    $page = translationPage();
    $check = $this->service->submit($page, $this->translator, 'en', false, 'Erreur');

    $this->service->resolve($check, $this->admin, 'in_progress', 'Je m\'en occupe');
    expect($check->fresh()->status)->toBe('in_progress')
        ->and($check->fresh()->admin_response)->toBe('Je m\'en occupe');

    $this->service->resolve($check, $this->admin, 'fixed');
    $fresh = $check->fresh();
    expect($fresh->status)->toBe('fixed')
        ->and($fresh->isValidated())->toBeTrue()
        ->and($fresh->handled_by)->toBe($this->admin->id)
        ->and($fresh->admin_response)->toBe('Je m\'en occupe');
});

test('une traduction correcte ne se traite pas', function () {
    $check = $this->service->submit(translationPage(), $this->translator, 'en', true);

    expect(fn () => $this->service->resolve($check, $this->admin, 'fixed'))
        ->toThrow(DomainException::class);
});

test('redemander une vérification repasse la langue à vérifier', function () {
    $page = translationPage();
    $this->service->submit($page, $this->translator, 'en', true);
    $this->service->submit($page, $this->translator, 'it', true);

    $this->service->requestRecheck($page, 'en');

    expect($page->translationChecks()->pluck('language')->all())->toBe(['it']);
});

test('la clôture annuelle FR n\'efface pas les traductions', function () {
    $page = translationPage();
    $this->service->submit($page, $this->translator, 'en', true);

    app(VerificationReviewService::class)->closeAnnualVerification($page);

    expect($page->translationChecks()->count())->toBe(1);
});

// ─── FILTRES ──────────────────────────────────────────────────────

test('les compteurs correspondent aux lignes de chaque filtre', function () {
    $todo = translationPage();
    $half = translationPage();
    $toFix = translationPage();
    $this->service->submit($half, $this->translator, 'en', true);
    $this->service->submit($toFix, $this->translator, 'en', false, 'Erreur');
    $this->service->submit($toFix, $this->translator, 'it', true);

    $stats = $this->service->stats(VerificationPage::forTranslation(), ['en', 'it']);

    expect($stats)->toMatchArray(['all' => 3, 'to_check' => 2, 'to_fix' => 1, 'in_progress' => 0, 'validated' => 2]);

    $enOnly = $this->service->stats(VerificationPage::forTranslation(), ['en']);
    expect($enOnly)->toMatchArray(['to_check' => 1, 'to_fix' => 1, 'validated' => 1]);

    // 3 pages × 2 langues − 3 verdicts
    expect($this->service->countToCheck())->toBe(3);
});

// ─── COMPOSANTS LIVEWIRE ──────────────────────────────────────────

test('le formulaire traducteur enregistre un verdict à corriger', function () {
    $page = translationPage();

    Livewire::actingAs($this->translator)
        ->test(TranslationForm::class, ['page' => $page])
        ->set('verdicts.en', 'to_fix')
        ->call('submit', 'en')
        ->assertHasErrors(['comments.en' => 'required_if'])
        ->set('comments.en', 'Le menu n\'est pas traduit')
        ->call('submit', 'en')
        ->assertHasNoErrors();

    $check = $page->fresh()->translationCheckFor('en');
    expect($check->status)->toBe('to_fix')
        ->and($check->comment)->toBe('Le menu n\'est pas traduit');
});

test('le formulaire traducteur renseigne une URL manquante', function () {
    $page = translationPage(['url_it' => null]);

    Livewire::actingAs($this->translator)
        ->test(TranslationForm::class, ['page' => $page])
        ->set('urls.it', 'pas-une-url')
        ->call('saveUrl', 'it')
        ->assertHasErrors(['urls.it'])
        ->set('urls.it', 'https://www.verdontourisme.com/it/pagina/')
        ->call('saveUrl', 'it')
        ->assertHasNoErrors();

    expect($page->fresh()->url_it)->toBe('https://www.verdontourisme.com/it/pagina/');
});

test('le super-admin marque une correction comme faite', function () {
    $page = translationPage();
    $this->service->submit($page, $this->translator, 'it', false, 'Erreur');

    Livewire::actingAs($this->admin)
        ->test(TranslationsManager::class)
        ->call('openPage', $page->id)
        ->set('responses.it', 'Corrigé sur le site')
        ->call('markFixed', 'it')
        ->assertHasNoErrors();

    $check = $page->fresh()->translationCheckFor('it');
    expect($check->status)->toBe('fixed')
        ->and($check->admin_response)->toBe('Corrigé sur le site');
});
