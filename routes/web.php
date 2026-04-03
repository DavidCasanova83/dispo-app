<?php

use App\Http\Controllers\AgendaPdfController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Redirection simple
Route::redirect(
    '/storage/pdfs/guide-du-partenaire-2026_1764863478_aQymTR8s9i.pdf',
    '/storage/pdfs/guide-du-partenaire.pdf',
    301
);

Route::get('/storage/pdfs/guide-du-partenaire.pdf', function () {
    return redirect()->away('https://plan.verdontourisme.com/brochures/2025/guide-pratique/Guide-du-partenaire_2026.pdf', 301);
});

// Servir le PDF de l'agenda en cours avec contrôle du cache (5 min)
Route::get('/storage/agendas/agenda-en-cours.pdf', [AgendaPdfController::class, 'current'])
    ->name('pdf.agenda.current');

// Route pour commander des images (authentification requise)
Route::get('/commander-images', \App\Livewire\PublicImageOrderForm::class)
    ->middleware(['auth', 'throttle:order-form'])
    ->name('order.images');

// Redirections permanentes /brochures vers /brochures-oti-vt
Route::redirect('/brochures', '/brochures-oti-vt', 301);
Route::redirect('/brochures/{categorySlug}', '/brochures-oti-vt/{categorySlug}', 301);
Route::redirect('/brochures/{categorySlug}/{subCategorySlug}', '/brochures-oti-vt/{categorySlug}/{subCategorySlug}', 301);

// Routes publiques pour les brochures OTI-VT
Route::get('/brochures-oti-vt', \App\Livewire\PublicBrochuresOtiVt::class)
     ->name('brochures-oti-vt');
Route::get('/brochures-oti-vt/{categorySlug}', \App\Livewire\PublicBrochuresOtiVt::class)
     ->name('brochures-oti-vt.category');
Route::get('/brochures-oti-vt/{categorySlug}/{subCategorySlug}', \App\Livewire\PublicBrochuresOtiVt::class)
     ->name('brochures-oti-vt.subcategory');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

Route::middleware(['auth', 'approved'])->group(function () {
    // Route pour la gestion des brochures par le responsable
    Route::get('/mes-brochures', \App\Livewire\MyBrochuresManager::class)
        ->name('mes-brochures');

    // Settings - available to all authenticated approved users
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Nouvelle route pour la page de test
    Route::view('test', 'test')->name('test');

    // Route pour afficher les hébergements - requires view-disponibilites permission
    Route::middleware(['permission:view-disponibilites'])->group(function () {
        Route::get('accommodations', function () {
            $accommodations = \App\Models\Accommodation::orderBy('name')->get();

            // Calcul des statistiques
            $stats = [
                'total' => $accommodations->count(),
                'by_status' => $accommodations->groupBy('status')->map->count(),
                'by_type' => $accommodations->whereNotNull('type')->groupBy('type')->map->count(),
                'by_city' => $accommodations->whereNotNull('city')->groupBy('city')->map->count(),
                'with_email' => $accommodations->whereNotNull('email')->count(),
                'with_phone' => $accommodations->whereNotNull('phone')->count(),
                'with_website' => $accommodations->whereNotNull('website')->count(),
            ];

            // Top 5 des villes
            $topCities = $accommodations->whereNotNull('city')
                ->groupBy('city')
                ->map->count()
                ->sortDesc()
                ->take(5);
            return view('accommodations', compact('accommodations', 'stats', 'topCities'));
        })->name('accommodations');
    });

    // Routes pour l'administration des utilisateurs - requires manage-users permission (Super-admin only)
    Route::middleware(['permission:manage-users'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', \App\Livewire\Admin\UsersList::class)->name('users');
        Route::get('/contact-submissions', \App\Livewire\Admin\ContactFormSubmissions::class)->name('contact-submissions');
    });

    // Route pour la gestion des images - requires manage-images permission
    Route::middleware(['permission:manage-images'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/images', \App\Livewire\Admin\ImageManager::class)->name('images');
        Route::get('/images/statistics', \App\Livewire\Admin\BrochureStatistics::class)->name('images.statistics');
    });

    // Route pour la gestion du menu brochures OTI-VT - requires manage-brochure-menu permission
    Route::middleware(['permission:manage-brochure-menu'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/brochure-menu', \App\Livewire\Admin\BrochureMenuManager::class)->name('brochure-menu');
    });

    // Route pour la gestion des agendas - requires manage-agendas permission
    Route::middleware(['permission:manage-agendas'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/agendas', \App\Livewire\Admin\AgendaManager::class)->name('agendas');

    });

    // Route pour la gestion des commandes - requires manage-orders permission
    Route::middleware(['permission:manage-orders'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/commandes', \App\Livewire\Admin\OrderManager::class)->name('orders');
    });

    // Routes pour le module Qualification
    Route::prefix('qualification')->name('qualification.')->group(function () {
        // Page de sélection des villes - requires view-qualification permission
        Route::get('/', [\App\Http\Controllers\QualificationController::class, 'index'])
            ->middleware(['permission:view-qualification'])
            ->name('index');

        // Page de statistiques (Chart.js) - requires view-qualification permission
        Route::get('/statistiques', \App\Livewire\QualificationStatisticsV2::class)
            ->middleware(['permission:view-qualification'])
            ->name('statistics');

        // Page de statistiques V3 (normalisée) - requires view-qualification permission
        Route::get('/statistiques-v3', \App\Livewire\QualificationStatisticsV3::class)
            ->middleware(['permission:view-qualification'])
            ->name('statistics.v3');

        // Export des données - requires view-qualification permission
        Route::get('/export', [\App\Http\Controllers\QualificationController::class, 'export'])
            ->middleware(['permission:view-qualification'])
            ->name('export');

        // Dashboard spécifique d'une ville - requires view-qualification permission
        Route::get('/{city}', [\App\Http\Controllers\QualificationController::class, 'cityDashboard'])
            ->middleware(['permission:view-qualification'])
            ->name('city.dashboard')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // Formulaire de qualification pour une ville - requires fill-forms OR edit-qualification
        Route::get('/{city}/formulaire01', [\App\Http\Controllers\QualificationController::class, 'form'])
            ->middleware(['permission:fill-forms,edit-qualification'])
            ->name('city.form')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // Page de données (liste des entrées) pour une ville - requires edit-qualification permission
        Route::get('/{city}/data', [\App\Http\Controllers\QualificationController::class, 'data'])
            ->middleware(['permission:edit-qualification'])
            ->name('city.data')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // Page d'édition d'une qualification - requires edit-qualification permission
        Route::get('/{city}/data/{id}/edit', [\App\Http\Controllers\QualificationController::class, 'edit'])
            ->middleware(['permission:edit-qualification'])
            ->name('edit')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // API pour sauvegarder les données du formulaire - requires fill-forms OR edit-qualification
        Route::post('/save', [\App\Http\Controllers\QualificationController::class, 'save'])
            ->middleware(['permission:fill-forms,edit-qualification'])
            ->name('save');
    });
});


// GET : affiche la page de confirmation (middleware signed, pas d'action en BDD)
Route::get('/accommodation/response/{accommodation}', [App\Http\Controllers\AccommodationResponseController::class, 'showConfirmation'])
    ->name('accommodation.response')
    ->middleware('signed');

// POST : enregistre la réponse après confirmation (middleware signed = même URL signée)
Route::post('/accommodation/response/{accommodation}', [App\Http\Controllers\AccommodationResponseController::class, 'processResponse'])
    ->name('accommodation.response.process')
    ->middleware('signed');

// Fallback pour les anciens liens avec token (rétrocompatibilité)
Route::get('/accommodation/response', [App\Http\Controllers\AccommodationResponseController::class, 'handleResponseLegacy'])
    ->name('accommodation.response.legacy');

// Signalement de problème par un hébergeur
Route::post('/accommodation/report-problem', [App\Http\Controllers\AccommodationResponseController::class, 'reportProblem'])
    ->name('accommodation.report-problem')
    ->middleware('throttle:5,10');

// API publique pour les images
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/images', [App\Http\Controllers\Api\ImageApiController::class, 'index'])->name('images.index');
    Route::get('/images/{id}', [App\Http\Controllers\Api\ImageApiController::class, 'show'])->name('images.show');
});

require __DIR__ . '/auth.php';
