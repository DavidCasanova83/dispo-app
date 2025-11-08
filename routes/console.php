<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Récupération automatique des hébergements Apidae tous les jours à 5h du matin
// Schedule::command('apidae:fetch --all')
//     ->dailyAt('05:00')                    
//     ->withoutOverlapping()                
//     ->runInBackground()                   
//     ->onOneServer()                       
//     ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));