<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Activer les agendas en attente tous les jours à 00:01
Schedule::command('agendas:activate-pending')->dailyAt('00:01');

// Repasser les pages validées depuis plus d'1 an en "à vérifier", chaque jour à 03:00
Schedule::command('verification:revalidate-aged')->dailyAt('03:00');

// Libérer 2 pages par utilisateur (plafond strict) chaque dimanche à 02:00
Schedule::command('verification:release-weekly')->weeklyOn(0, '02:00');
