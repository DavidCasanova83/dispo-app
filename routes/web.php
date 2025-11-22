<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Route publique pour commander des images (pas d'authentification requise)
// Protection: 10 consultations par minute, 5 soumissions par heure
Route::get('/commander-images', \App\Livewire\PublicImageOrderForm::class)
    ->middleware('throttle:order-form')
    ->name('order.images');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

Route::middleware(['auth', 'approved'])->group(function () {
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
    });

    // Route pour la gestion des images - requires manage-images permission
    Route::middleware(['permission:manage-images'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/images', \App\Livewire\Admin\ImageManager::class)->name('images');
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

// Route publique pour les réponses de disponibilité des hébergements
Route::get('/accommodation/response', [App\Http\Controllers\AccommodationResponseController::class, 'handleResponse'])
    ->name('accommodation.response');

// API publique pour les images
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/images', [App\Http\Controllers\Api\ImageApiController::class, 'index'])->name('images.index');
    Route::get('/images/{id}', [App\Http\Controllers\Api\ImageApiController::class, 'show'])->name('images.show');
});

require __DIR__ . '/auth.php';
