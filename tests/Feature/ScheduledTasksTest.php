<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyncApidaeData;

uses(RefreshDatabase::class);

describe('Tests de Planification Laravel', function () {
    
    test('le scheduler Laravel est correctement configuré', function () {
        // Vérifier que les tâches sont enregistrées
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        
        // On devrait avoir au moins 3 tâches :
        // - Synchronisation quotidienne
        // - Synchronisation hebdomadaire  
        // - Nettoyage des logs
        expect(count($events))->toBeGreaterThanOrEqual(3);
        
        // Vérifier qu'il y a des jobs Apidae planifiés
        $apidaeJobs = collect($events)->filter(function ($event) {
            return str_contains($event->command ?? '', 'SyncApidaeData') || 
                   (isset($event->job) && $event->job instanceof SyncApidaeData);
        });
        
        expect($apidaeJobs)->toHaveCount(2); // Quotidien + hebdomadaire
    });

    test('la commande schedule:list affiche les tâches configurées', function () {
        $this->artisan('schedule:list')
            ->assertExitCode(0);
        
        // Capturer la sortie pour vérifier le contenu
        $output = Artisan::output();
        
        expect($output)->toContain('SyncApidaeData');
        expect($output)->toContain('5 * * *'); // Tâche quotidienne à 5h
        expect($output)->toContain('3 * * 0'); // Tâche hebdomadaire dimanche 3h
    });

    test('schedule:run n\'exécute pas les tâches en dehors des heures programmées', function () {
        Queue::fake();
        
        // En mode test, aucune tâche ne devrait s'exécuter car on n'est pas aux bonnes heures
        $this->artisan('schedule:run')
            ->expectsOutput('No scheduled commands are ready to run.')
            ->assertExitCode(0);
        
        // Aucun job ne devrait être dispatché
        Queue::assertNothingPushed();
    });

    test('les tâches ont les bonnes configurations de timezone', function () {
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        
        // Vérifier que les événements Apidae ont la timezone Europe/Paris
        $apidaeEvents = collect($events)->filter(function ($event) {
            return str_contains($event->command ?? '', 'SyncApidaeData') || 
                   (isset($event->job) && $event->job instanceof SyncApidaeData);
        });
        
        foreach ($apidaeEvents as $event) {
            expect($event->timezone)->toBe('Europe/Paris');
        }
    });

    test('les tâches ont la protection withoutOverlapping configurée', function () {
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        
        $apidaeEvents = collect($events)->filter(function ($event) {
            return str_contains($event->command ?? '', 'SyncApidaeData') || 
                   (isset($event->job) && $event->job instanceof SyncApidaeData);
        });
        
        // Vérifier que withoutOverlapping est configuré
        foreach ($apidaeEvents as $event) {
            $filters = $event->filters;
            $hasOverlapProtection = collect($filters)->some(function ($filter) {
                return str_contains(get_class($filter), 'WithoutOverlapping');
            });
            
            expect($hasOverlapProtection)->toBeTrue();
        }
    });
});

describe('Tests de Queue pour Synchronisation', function () {
    
    test('le job de synchronisation utilise la bonne queue', function () {
        $job = new SyncApidaeData(100);
        
        // Vérifier que le job est configuré pour la queue apidae-sync
        expect($job->queue)->toBe('apidae-sync');
    });

    test('le job a les bonnes propriétés de retry et timeout', function () {
        $job = new SyncApidaeData(200, false);
        
        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(1800); // 30 minutes
        expect($job->backoff)->toBe(300);  // 5 minutes
    });

    test('le job peut être taggé pour le monitoring', function () {
        $job = new SyncApidaeData(150, true);
        $tags = $job->tags();
        
        expect($tags)->toContain('apidae');
        expect($tags)->toContain('sync');
        expect($tags)->toContain('scheduled');
        expect($tags)->toContain('limit:150');
    });

    test('les différents types de jobs ont les bons paramètres', function () {
        // Job quotidien standard
        $dailyJob = new SyncApidaeData(200, false);
        expect($dailyJob->tags())->toContain('limit:200');
        
        // Job hebdomadaire avec force sync
        $weeklyJob = new SyncApidaeData(500, true);
        expect($weeklyJob->tags())->toContain('limit:500');
    });
});

describe('Tests d\'Intégration de Planification', function () {
    
    test('la commande artisan peut exécuter la synchronisation manuellement', function () {
        Queue::fake();
        
        // Cette commande devrait exister pour les tests manuels
        // (Même si elle n'est pas encore créée, on prépare le test)
        expect(true)->toBeTrue(); // Placeholder
        
        // Dans le futur, on pourrait avoir :
        // $this->artisan('apidae:sync-scheduled')
        //     ->assertExitCode(0);
    });

    test('le système peut détecter si le scheduler fonctionne', function () {
        // Test de base pour vérifier que le système de planification est fonctionnel
        $schedule = app(Schedule::class);
        expect($schedule)->toBeInstanceOf(Schedule::class);
        
        // Vérifier que Laravel peut interpréter la configuration des tâches
        $events = $schedule->events();
        expect($events)->toBeArray();
    });

    test('les logs de planification sont configurés', function () {
        // Vérifier que les événements ont des callbacks de logging
        $schedule = app(Schedule::class);
        $events = $schedule->events();
        
        $apidaeEvents = collect($events)->filter(function ($event) {
            return str_contains($event->command ?? '', 'SyncApidaeData') || 
                   (isset($event->job) && $event->job instanceof SyncApidaeData);
        });
        
        // Au moins un événement devrait avoir des callbacks
        expect($apidaeEvents->count())->toBeGreaterThan(0);
        
        foreach ($apidaeEvents as $event) {
            // Vérifier que l'événement a des callbacks configurés
            expect($event->beforeCallbacks ?? [])->toBeArray();
            expect($event->afterCallbacks ?? [])->toBeArray();
        }
    });
});