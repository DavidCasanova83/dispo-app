<?php

use App\Livewire\PublicBrochuresOtiVt;
use App\Models\Author;
use App\Models\Category;
use App\Models\Image;
use App\Models\Sector;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\BrochureLanguageMatcher;
use App\Services\BrochureVersionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->author = Author::create(['name' => 'Verdon Tourisme']);
    $this->category = Category::create(['name' => "Guides d'hébergements"]);
});

/**
 * Brochure (ou document) avec ses fichiers sur le disque public simulé.
 */
function brochure(string $title, array $attributes = []): Image
{
    static $n = 0;
    $n++;
    $slug = \Illuminate\Support\Str::slug($title)."-{$n}";
    Storage::disk('public')->put("images/{$slug}.jpg", 'couverture');
    Storage::disk('public')->put("images/thumbnails/thumb_{$slug}.jpg", 'vignette');
    Storage::disk('public')->put("pdfs/{$slug}.pdf", '%PDF');

    return Image::factory()->create(array_merge([
        'title' => $title,
        'name' => "{$slug}.pdf",
        'filename' => "{$slug}.jpg",
        'path' => "images/{$slug}.jpg",
        'url' => "images/{$slug}.jpg",
        'thumbnail_path' => "images/thumbnails/thumb_{$slug}.jpg",
        'pdf_path' => "pdfs/{$slug}.pdf",
        'author_id' => test()->author->id,
        'category_id' => test()->category->id,
        'edition_year' => 2026,
        'is_offline' => false,
    ], $attributes));
}

// ---------------------------------------------------------------------------
// Reprise : langue lue dans le titre, appariement
// ---------------------------------------------------------------------------

test('la langue des anciens titres est reconnue et retirée du titre', function () {
    $matcher = new BrochureLanguageMatcher;

    expect($matcher->detect('English - Hôtels'))->toMatchArray(['language' => 'en', 'title' => 'Hôtels'])
        ->and($matcher->detect("English Version - Magazine Sources d'Envies"))->toMatchArray(['language' => 'en', 'title' => "Magazine Sources d'Envies"])
        ->and($matcher->detect('Carte Touristique - Verdon Tourisme IT'))->toMatchArray(['language' => 'it', 'title' => 'Carte Touristique - Verdon Tourisme'])
        ->and($matcher->detect('Carte Touristique - Verdon Tourisme FR/EN'))->toMatchArray([
            'language' => 'fr', 'title' => 'Carte Touristique - Verdon Tourisme', 'extra_languages' => ['en'],
        ])
        ->and($matcher->detect('Guide Pratique')['language'])->toBe('fr')
        ->and($matcher->detect('Kit du randonneur')['language'])->toBe('fr')
        ->and($matcher->detect('Hotels', 'pdfs/english-hotels.pdf')['language'])->toBe('en');
});

test('chaque document EN trouve la brochure française la plus proche', function () {
    $matcher = new BrochureLanguageMatcher;
    $french = [
        ['id' => 1, 'title' => 'Hébergements collectifs', 'category_id' => 1],
        ['id' => 2, 'title' => 'Location de vacances', 'category_id' => 1],
        ['id' => 3, 'title' => 'Saint André - Locations de Vacances', 'category_id' => 1],
        ['id' => 4, 'title' => 'Saint André et alentours - Hébergements', 'category_id' => 1],
        ['id' => 5, 'title' => 'Camping et aires de camping-cars', 'category_id' => 1],
        ['id' => 6, 'title' => 'Découvrir le territoire Verdon Tourisme', 'category_id' => 2],
    ];
    $documents = [
        ['id' => 10, 'title' => 'Collectifs', 'category_id' => 1],
        ['id' => 11, 'title' => 'Location de vacances', 'category_id' => 1],
        ['id' => 12, 'title' => 'Saint-André-les-Alpes et alentours', 'category_id' => 1],
        ['id' => 13, 'title' => 'Campings et aires de camping-car', 'category_id' => 1],
        ['id' => 14, 'title' => "Magazine Sources d'Envies", 'category_id' => 2],
    ];

    $matches = $matcher->match($documents, $french);

    expect($matches[10]['brochure_id'])->toBe(1)
        ->and($matches[11]['brochure_id'])->toBe(2)
        ->and($matches[12]['brochure_id'])->toBe(4)
        ->and($matches[13]['brochure_id'])->toBe(5)
        ->and($matches[14]['brochure_id'])->toBeNull();
});

// ---------------------------------------------------------------------------
// Modèle
// ---------------------------------------------------------------------------

test('une version reprend le classement de sa brochure et le suit', function () {
    $sector = Sector::create(['name' => 'Annot']);
    $responsable = User::factory()->create(['approved' => true]);
    $main = brochure('Hôtels', ['sector_id' => $sector->id, 'responsable_id' => $responsable->id]);
    $document = brochure('English - Hôtels', ['category_id' => null, 'display_order' => 4]);

    $version = app(BrochureVersionService::class)->attach($main, $document, 'en');

    expect($version->version_of_id)->toBe($main->id)
        ->and($version->language)->toBe('en')
        ->and($version->category_id)->toBe($this->category->id)
        ->and($version->sector_id)->toBe($sector->id)
        ->and($version->responsable_id)->toBe($responsable->id)
        ->and($version->slug)->toBeNull()
        ->and($version->display_order)->toBeNull()
        ->and($main->slug)->not->toBeEmpty();

    $other = Category::create(['name' => 'Cartes']);
    $main->update(['category_id' => $other->id]);

    expect($version->fresh()->category_id)->toBe($other->id);
});

test('une brochure n\'a qu\'une version par langue, et une version n\'a pas de versions', function () {
    $service = app(BrochureVersionService::class);
    $main = brochure('Hôtels');
    $service->attach($main, brochure('English - Hôtels'), 'en');

    expect(fn () => $service->attach($main, brochure('English - Hôtels bis'), 'en'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->attach($main->versions->first(), brochure('Italiano - Hotel'), 'it'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->attach($main, brochure('Hôtels FR'), 'fr'))
        ->toThrow(InvalidArgumentException::class);
});

test('les éditions de la carte suivent les versions et les langues contenues', function () {
    $service = app(BrochureVersionService::class);
    $carte = brochure('Carte touristique', ['extra_languages' => ['en']]);
    $italien = $service->attach($carte, brochure('Carte touristique IT'), 'it');

    $editions = $carte->fresh()->editions();
    expect(array_keys($editions))->toBe(['fr', 'en', 'it'])
        ->and($editions['en']->id)->toBe($carte->id)
        ->and($editions['it']->id)->toBe($italien->id);

    // Une version anglaise dédiée prend le pas sur le document bilingue
    $anglais = $service->attach($carte, brochure('Map EN'), 'en');
    expect($carte->fresh()->editions()['en']->id)->toBe($anglais->id);

    // Version hors ligne : absente pour les visiteurs
    $italien->update(['is_offline' => true]);
    expect(array_keys($carte->fresh()->editions()))->toBe(['fr', 'en'])
        ->and(array_keys($carte->fresh()->editions(includeOffline: true)))->toBe(['fr', 'en', 'it']);
});

test('supprimer une brochure supprime ses versions et leurs fichiers', function () {
    $main = brochure('Hôtels');
    $version = app(BrochureVersionService::class)->attach($main, brochure('English - Hôtels'), 'en');

    $main->delete();

    expect(Image::find($version->id))->toBeNull()
        ->and(Storage::disk('public')->exists($version->pdf_path))->toBeFalse();
});

test('une version ajoutée avec son PDF copie la couverture française', function () {
    $main = brochure('Guide pratique');

    $version = app(BrochureVersionService::class)->create($main, 'it', [
        'title' => 'Guida pratica',
        'edition_year' => 2025,
    ], UploadedFile::fake()->create('guida.pdf', 120, 'application/pdf'), null, null);

    expect($version->version_of_id)->toBe($main->id)
        ->and($version->title)->toBe('Guida pratica')
        ->and($version->pdf_path)->toBe('pdfs/'.$main->slug.'-it.pdf')
        ->and($version->path)->not->toBe($main->path)
        ->and(Storage::disk('public')->exists($version->path))->toBeTrue()
        ->and($version->isOutdatedVersion())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Catalogue public
// ---------------------------------------------------------------------------

test('le catalogue montre une carte par brochure, avec ses pastilles de langue', function () {
    $service = app(BrochureVersionService::class);
    $hotels = brochure('Hôtels');
    $service->attach($hotels, brochure('Hotels', ['title' => 'English - Hôtels']), 'en');
    brochure('Chambres d\'hôtes');

    $component = Livewire::test(PublicBrochuresOtiVt::class);

    $component->assertViewHas('brochures', fn ($brochures) => $brochures->pluck('title')->sort()->values()->all() === ["Chambres d'hôtes", 'Hôtels'])
        ->assertSee('Langues de la brochure')
        ->assertViewHas('languageCounts', ['en' => 1, 'it' => 0]);
});

test('le filtre de langue ne garde que les brochures lisibles dans la langue', function () {
    $service = app(BrochureVersionService::class);
    $hotels = brochure('Hôtels');
    $service->attach($hotels, brochure('English - Hôtels'), 'en');
    brochure('Carte touristique', ['extra_languages' => ['en']]);
    brochure('Chambres d\'hôtes');
    $hors = brochure('Insolites');
    $service->attach($hors, brochure('English - Insolites', ['is_offline' => true]), 'en');

    Livewire::withQueryParams(['langue' => 'en'])
        ->test(PublicBrochuresOtiVt::class)
        ->assertViewHas('activeLanguage', 'en')
        ->assertViewHas('brochures', fn ($brochures) => $brochures->pluck('title')->sort()->values()->all() === ['Carte touristique', 'Hôtels']);

    Livewire::test(PublicBrochuresOtiVt::class)
        ->call('setLanguage', 'it')
        ->assertViewHas('brochures', fn ($brochures) => $brochures->isEmpty());
});

test('la recherche trouve une brochure par le titre de sa version', function () {
    $main = brochure('Guide pratique');
    app(BrochureVersionService::class)->attach($main, brochure('Practical guide'), 'en');

    Livewire::test(PublicBrochuresOtiVt::class)
        ->set('search', 'Practical')
        ->assertViewHas('brochures', fn ($brochures) => $brochures->pluck('id')->all() === [$main->id]);
});

// ---------------------------------------------------------------------------
// Adresse permanente
// ---------------------------------------------------------------------------

test('l\'adresse permanente mène au fichier de la langue, sinon au français', function () {
    $main = brochure('Visites guidées groupes');
    $version = app(BrochureVersionService::class)->attach($main, brochure('Group tours'), 'en');

    $this->get("/brochure/{$main->slug}")->assertRedirect(asset('storage/'.$main->pdf_path));
    $this->get("/brochure/{$main->slug}/en")->assertRedirect(asset('storage/'.$version->pdf_path));
    $this->get("/brochure/{$main->slug}/it")->assertRedirect(asset('storage/'.$main->pdf_path));
    $this->get('/brochure/inconnue/en')->assertNotFound();

    $main->update(['is_offline' => true]);
    $this->get("/brochure/{$main->slug}/en")->assertNotFound();

    expect($main->permalink('en'))->toBe(url("/brochure/{$main->slug}/en"))
        ->and($version->permalink('en'))->toBe(url("/brochure/{$main->slug}/en"));
});

// ---------------------------------------------------------------------------
// Fenêtre de modification
// ---------------------------------------------------------------------------

test('un administrateur ajoute puis supprime une version depuis la fenêtre de modification', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $admin->assignRole('Super-admin');
    $main = brochure('Hôtels', ['responsable_id' => $admin->id]);

    $component = Livewire::actingAs($admin)
        ->test(PublicBrochuresOtiVt::class)
        ->call('openEditModal', $main->id)
        ->call('openVersionForm', 'en')
        ->assertSet('versionTitle', 'Hôtels')
        ->set('versionTitle', 'Hotels')
        ->set('versionContentFile', UploadedFile::fake()->create('hotels.pdf', 50, 'application/pdf'))
        ->call('addVersion')
        ->assertHasNoErrors()
        ->assertSet('versionFormLanguage', '');

    $version = $main->fresh()->versionIn('en');
    expect($version)->not->toBeNull()
        ->and($version->title)->toBe('Hotels');

    // Ouverte sur la version, la fenêtre n'exige ni auteur ni responsable
    $component->call('openEditModal', $version->id)
        ->assertSet('editTitle', 'Hotels')
        ->set('editEditionYear', 2025)
        ->call('updateImage')
        ->assertHasNoErrors()
        ->assertSet('editingImage.id', $main->id);
    expect($version->fresh()->edition_year)->toBe(2025);

    $component->call('removeVersion', $version->id);
    expect($main->fresh()->versions)->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// Commande de reprise
// ---------------------------------------------------------------------------

test('la reprise rattache les anciennes brochures traduites et signale les doublons', function () {
    $english = SubCategory::create(['name' => 'English version', 'category_id' => $this->category->id]);
    $hotels = brochure('Hôtels');
    $collectifs = brochure('Hébergements collectifs');
    $carte = brochure('Carte Touristique - Verdon Tourisme FR/EN');
    $hotelsEn = brochure('English - Hôtels', ['sub_category_id' => $english->id, 'edition_year' => 2025]);
    $collectifsEn = brochure('English - Collectifs', ['sub_category_id' => $english->id]);
    $collectifsEnBis = brochure('English - Collectifs', ['sub_category_id' => $english->id, 'edition_year' => 2024]);
    $carteIt = brochure('Carte Touristique - Verdon Tourisme IT');
    $magazine = brochure("English Version - Magazine Sources d'Envies");

    // Aperçu : rien ne change
    $this->artisan('brochures:lier-versions')->assertSuccessful();
    expect($hotelsEn->fresh()->version_of_id)->toBeNull();

    $this->artisan('brochures:lier-versions', ['--appliquer' => true])->assertSuccessful();

    expect($hotelsEn->fresh())->version_of_id->toBe($hotels->id)->language->toBe('en')->title->toBe('Hôtels')
        ->and($collectifsEn->fresh()->version_of_id)->toBe($collectifs->id)
        ->and($collectifsEnBis->fresh()->version_of_id)->toBeNull()
        ->and($carteIt->fresh())->version_of_id->toBe($carte->id)->language->toBe('it')
        ->and($carte->fresh())->title->toBe('Carte Touristique - Verdon Tourisme')->extra_languages->toBe(['en'])
        ->and($magazine->fresh()->version_of_id)->toBeNull()
        ->and($hotelsEn->fresh()->sub_category_id)->toBeNull();

    // Rattachement imposé du document que l'appariement ne trouve pas
    $decouvrir = brochure('Découvrir le territoire');
    $this->artisan('brochures:lier-versions', ['--appliquer' => true, '--paire' => ["{$magazine->id}:{$decouvrir->id}"]])
        ->assertSuccessful();
    expect($magazine->fresh()->version_of_id)->toBe($decouvrir->id);
});

// ---------------------------------------------------------------------------
// Écrans de gestion
// ---------------------------------------------------------------------------

test('admin, Mes brochures et statistiques montrent les versions sur la ligne de leur brochure', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['approved' => true, 'approved_at' => now()]);
    $admin->assignRole('Super-admin');
    $main = brochure('Hôtels', ['responsable_id' => $admin->id]);
    $version = app(BrochureVersionService::class)->attach($main, brochure('Hotels', ['edition_year' => 2024]), 'en');
    \App\Models\BrochureClick::create(['image_id' => $version->id, 'button_type' => 'consulter', 'ip_address' => '127.0.0.1']);

    Livewire::actingAs($admin)->test(\App\Livewire\Admin\ImageManager::class)
        ->assertViewHas('imagesList', fn ($list) => $list->pluck('id')->all() === [$main->id])
        ->assertSee('EN 2024');

    Livewire::actingAs($admin)->test(\App\Livewire\MyBrochuresManager::class)
        ->assertViewHas('brochures', fn ($list) => $list->pluck('id')->all() === [$main->id])
        ->assertSee('EN 2024')
        ->call('openEditModal', $version->id)
        ->assertSee('Modifier la version anglaise');

    Livewire::actingAs($admin)->test(\App\Livewire\Admin\BrochureStatistics::class)
        ->assertSee('Par langue');
});

test('cache des routes périmé : la reprise ne modifie rien et le catalogue reste affiché', function () {
    $main = brochure('Hôtels');
    $document = brochure('English - Hôtels');
    app('router')->setRoutes(new \Illuminate\Routing\RouteCollection);

    $this->artisan('brochures:lier-versions', ['--appliquer' => true])->assertFailed();

    expect($document->fresh()->version_of_id)->toBeNull()
        ->and($main->permalink('en'))->toBeNull();
});

test('relancée après coup, la reprise signale encore la sous-catégorie vidée et son menu', function () {
    $english = SubCategory::create(['name' => 'English version', 'category_id' => $this->category->id]);
    \App\Models\BrochureMenuItem::create(['title' => 'English version', 'url' => '/brochures-oti-vt/guides-dhebergements/'.$english->slug]);
    $main = brochure('Hôtels');
    app(BrochureVersionService::class)->attach($main, brochure('English - Hôtels', ['sub_category_id' => $english->id]), 'en');
    brochure("English Version - Magazine Sources d'Envies");

    $this->artisan('brochures:lier-versions', ['--appliquer' => true])
        ->expectsOutputToContain('English version')
        ->assertSuccessful();
});
