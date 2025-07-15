<?php

namespace App\Jobs;

use App\Services\ApidaeService;
use App\Services\AccommodationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Exception;

class SyncApidaeData implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 3;
    public int $backoff = 300; // 5 minutes entre les tentatives

    private int $limit;
    private bool $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 200, bool $forceSync = false)
    {
        $this->limit = $limit;
        $this->forceSync = $forceSync;
        
        // Configuration de la queue
        $this->onQueue('apidae-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(ApidaeService $apidaeService, AccommodationService $accommodationService): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info('🚀 Début de la synchronisation Apidae programmée', [
                'limit' => $this->limit,
                'force_sync' => $this->forceSync,
                'scheduled_at' => now()->toISOString()
            ]);

            // Vérifier la configuration API
            $status = $apidaeService->getApiStatus();
            if (!$status['configured']) {
                throw new Exception('Configuration API Apidae incomplète');
            }

            // Récupérer les données depuis l'API
            $apiData = $apidaeService->fetchAccommodations($this->limit);
            
            if (empty($apiData)) {
                Log::warning('⚠️ Aucune donnée reçue de l\'API Apidae');
                return;
            }

            // Traiter et sauvegarder
            $results = $apidaeService->processAndSaveAccommodations($apiData);
            
            $duration = microtime(true) - $startTime;
            
            // Log du succès
            Log::info('✅ Synchronisation Apidae terminée avec succès', [
                'duration_seconds' => round($duration, 2),
                'created' => $results['created'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
                'total_processed' => $results['total_processed'],
                'api_records' => count($apiData)
            ]);

            // Nettoyer le cache après synchronisation
            $accommodationService->clearCache();

            // Envoyer notification de succès si erreurs
            if ($results['errors'] > 0) {
                $this->notifyErrors($results);
            }

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            Log::error('❌ Erreur lors de la synchronisation Apidae', [
                'error' => $e->getMessage(),
                'duration_seconds' => round($duration, 2),
                'limit' => $this->limit,
                'attempt' => $this->attempts()
            ]);

            // Relancer le job si ce n'est pas la dernière tentative
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
                return;
            }

            // Notification d'échec après épuisement des tentatives
            $this->notifyFailure($e);
            
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('💥 Échec définitif de la synchronisation Apidae', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'limit' => $this->limit
        ]);

        $this->notifyFailure($exception);
    }

    /**
     * Notify about synchronization errors.
     */
    private function notifyErrors(array $results): void
    {
        if ($results['errors'] > 5) { // Seulement si beaucoup d'erreurs
            Log::warning('📧 Notification: Erreurs détectées lors de la synchronisation', [
                'errors_count' => $results['errors'],
                'total_processed' => $results['total_processed']
            ]);
            
            // Ici vous pourriez envoyer un email ou une notification Slack
            // Mail::to('admin@votre-domaine.com')->send(new ApidaeSyncErrorsNotification($results));
        }
    }

    /**
     * Notify about synchronization failure.
     */
    private function notifyFailure(Exception $exception): void
    {
        Log::error('📧 Notification: Échec de la synchronisation Apidae', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        // Ici vous pourriez envoyer un email d'alerte critique
        // Mail::to('admin@votre-domaine.com')->send(new ApidaeSyncFailureNotification($exception));
    }

    /**
     * Get the tags for this job.
     */
    public function tags(): array
    {
        return ['apidae', 'sync', 'scheduled', "limit:{$this->limit}"];
    }
}
