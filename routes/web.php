<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

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

    // Routes pour les hébergements (utilise maintenant le contrôleur)
    Route::get('accommodations', [App\Http\Controllers\AccommodationController::class, 'index'])->name('accommodations');
    Route::get('accommodations/create', [App\Http\Controllers\AccommodationController::class, 'create'])->name('accommodations.create');
    Route::get('accommodations/{id}', [App\Http\Controllers\AccommodationController::class, 'show'])->name('accommodations.show');
});

require __DIR__ . '/auth.php';
