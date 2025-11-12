<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Nouvelle route pour la page de test
    Route::view('test', 'test')->name('test');

    // Route pour afficher les hébergements
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

    // Routes pour le module Qualification
    Route::prefix('qualification')->name('qualification.')->group(function () {
        // Page de sélection des villes
        Route::get('/', [\App\Http\Controllers\QualificationController::class, 'index'])->name('index');

        // Dashboard spécifique d'une ville
        Route::get('/{city}', [\App\Http\Controllers\QualificationController::class, 'cityDashboard'])
            ->name('city.dashboard')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // Formulaire de qualification pour une ville
        Route::get('/{city}/formulaire01', [\App\Http\Controllers\QualificationController::class, 'form'])
            ->name('city.form')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // Page de données (liste des entrées) pour une ville
        Route::get('/{city}/data', [\App\Http\Controllers\QualificationController::class, 'data'])
            ->name('city.data')
            ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');

        // API pour sauvegarder les données du formulaire
        Route::post('/save', [\App\Http\Controllers\QualificationController::class, 'save'])->name('save');
    });
});

// Route publique pour les réponses de disponibilité des hébergements
Route::get('/accommodation/response', [App\Http\Controllers\AccommodationResponseController::class, 'handleResponse'])
    ->name('accommodation.response');

require __DIR__ . '/auth.php';
