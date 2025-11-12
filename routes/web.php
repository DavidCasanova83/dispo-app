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
});

// Route publique pour les réponses de disponibilité des hébergements
Route::get('accommodation/response', [App\Http\Controllers\AccommodationResponseController::class, 'handleResponse'])
    ->name('accommodation.response');

require __DIR__ . '/auth.php';
