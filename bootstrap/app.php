<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // middleware d'approbation et rôles/permissions
        $middleware->alias([
            'approved' => \App\Http\Middleware\EnsureUserIsApproved::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        // Import Apidae tous les jours à 5h du matin
        $schedule->command('apidae:fetch --all')
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));

        // Traiter la queue des emails toutes les minutes
        $schedule->command('queue:work database --stop-when-empty --max-jobs=50 --max-time=50')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/mails-scheduler.log'));
        
        $schedule->command('emails:send-availability')
            ->dailyAt('06:00')
            ->appendOutputTo(storage_path('logs/emails-daily.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
