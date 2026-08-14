<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Activer les agendas en attente tous les jours à 00:01
Schedule::command('agendas:activate-pending')->dailyAt('00:01');

// ─── Cycle de vérification, chaque nuit ───────────────────────────────
// L'ordre compte : le renouvellement remet des pages en file d'attente, la
// distribution doit passer APRÈS pour les prendre en compte la nuit même.

// 1. Repasser en "à vérifier" les pages validées depuis plus d'1 an,
//    et remettre leurs assignations en file d'attente.
Schedule::command('verification:revalidate-aged')->dailyAt('03:00');

// 2. Compléter les pages actives de chaque relecteur jusqu'au plafond (2).
//    Le plafond limite les pages ouvertes EN MÊME TEMPS : le rythme quotidien
//    ne submerge personne, il remplace une page terminée dès le lendemain au
//    lieu d'attendre la semaine suivante.
Schedule::command('verification:release-pages')->dailyAt('03:30');
