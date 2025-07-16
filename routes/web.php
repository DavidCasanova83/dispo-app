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
    
    // Routes pour les logs d'activité
    Route::get('logs', [App\Http\Controllers\LogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}', [App\Http\Controllers\LogController::class, 'show'])->name('logs.show');
    Route::post('logs/clear', [App\Http\Controllers\LogController::class, 'clear'])->name('logs.clear');
});

// Routes publiques pour la gestion des statuts d'hébergement (pas d'authentification requise)
Route::get('accommodation/{apidae_id}/manage', [App\Http\Controllers\AccommodationController::class, 'manage'])
    ->name('accommodation.manage');
    
Route::post('accommodation/{apidae_id}/status', [App\Http\Controllers\AccommodationController::class, 'updateStatus'])
    ->name('accommodation.update-status');

require __DIR__ . '/auth.php';
