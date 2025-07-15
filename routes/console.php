<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planification de la synchronisation Apidae via Job Queue
Schedule::job(new \App\Jobs\SyncApidaeData(200))
    ->dailyAt('05:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping(60) // Évite les chevauchements, timeout 1h
    ->pingBefore('https://hc-ping.com/your-uuid-before') // Optionnel: healthcheck
    ->thenPing('https://hc-ping.com/your-uuid-after')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('📅 Tâche planifiée Apidae démarrée avec succès');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('📅 Échec du démarrage de la tâche planifiée Apidae');
    });

// Alternative: Synchronisation hebdomadaire complète le dimanche
Schedule::job(new \App\Jobs\SyncApidaeData(500, true))
    ->weeklyOn(0, '03:00') // Dimanche à 3h
    ->timezone('Europe/Paris')
    ->withoutOverlapping(120);

// Planification de nettoyage des logs (optionnel)
Schedule::command('log:clear --keep=30')
    ->monthlyOn(1, '02:00')
    ->timezone('Europe/Paris');
