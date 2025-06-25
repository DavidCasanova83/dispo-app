<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccommodationController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Nouvelle route pour la page de test
    Route::view('test', 'test')->name('test');

    // Routes pour les hébergements
    Route::get('accommodations', [AccommodationController::class, 'index'])->name('accommodations');
    Route::get('accommodations/{id}', [AccommodationController::class, 'show'])->name('accommodations.show');
    Route::get('accommodations/active', [AccommodationController::class, 'active'])->name('accommodations.active');
    Route::get('accommodations/stats', [AccommodationController::class, 'stats'])->name('accommodations.stats');
});

require __DIR__ . '/auth.php';
