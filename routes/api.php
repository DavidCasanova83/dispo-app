<?php

use App\Http\Controllers\Api\AccommodationApiController;
use App\Http\Controllers\Api\ContactFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group.
|
*/

Route::get('/accommodations', [AccommodationApiController::class, 'index']);

// WordPress Contact Form 7 submissions (protected by API token)
Route::post('/contact-form/submit', [ContactFormController::class, 'store'])
    ->middleware('wordpress.api');
