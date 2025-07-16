<?php

namespace App\Jobs;

use App\Mail\AccommodationStatusUpdateMail;
use App\Models\Accommodation;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAccommodationNotificationEmails implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue to 'emails' for better organization
        $this->queue = 'emails';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting accommodation notification emails job');

        // Get accommodations with email addresses
        $accommodations = Accommodation::whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($accommodations->isEmpty()) {
            Log::info('No accommodations with email addresses found');
            
            ActivityLog::logActivity(
                'email_notification',
                'no_emails_found',
                'system',
                null,
                [],
                'Aucun hébergement avec email trouvé pour l\'envoi des notifications',
                'warning'
            );
            
            return;
        }

        $sentCount = 0;
        $errorCount = 0;

        foreach ($accommodations as $accommodation) {
            try {
                // Send email immediately (not queued)
                Mail::to($accommodation->email)
                    ->send(new AccommodationStatusUpdateMail($accommodation));

                $sentCount++;

                // Log successful send
                ActivityLog::logActivity(
                    'email_notification',
                    'email_sent',
                    'accommodation',
                    $accommodation->apidae_id,
                    [
                        'accommodation_name' => $accommodation->name,
                        'email' => $accommodation->email,
                        'status' => $accommodation->status,
                    ],
                    "Email de notification envoyé à {$accommodation->name} ({$accommodation->email})"
                );

                Log::info("Email sent to {$accommodation->name} ({$accommodation->email})");

                // Small delay to respect rate limits
                usleep(100000); // 0.1 second delay

            } catch (\Exception $e) {
                $errorCount++;

                // Log error
                ActivityLog::logActivity(
                    'email_notification',
                    'email_error',
                    'accommodation',
                    $accommodation->apidae_id,
                    [
                        'accommodation_name' => $accommodation->name,
                        'email' => $accommodation->email,
                        'error' => $e->getMessage(),
                    ],
                    "Erreur lors de l'envoi d'email à {$accommodation->name} ({$accommodation->email}): {$e->getMessage()}",
                    'error'
                );

                Log::error("Failed to send email to {$accommodation->name} ({$accommodation->email}): {$e->getMessage()}");
            }
        }

        // Log summary
        ActivityLog::logActivity(
            'email_notification',
            'batch_completed',
            'system',
            null,
            [
                'total_accommodations' => $accommodations->count(),
                'sent_count' => $sentCount,
                'error_count' => $errorCount,
            ],
            "Envoi d'emails terminé: {$sentCount} envoyés, {$errorCount} erreurs sur {$accommodations->count()} hébergements"
        );

        Log::info("Accommodation notification emails job completed: {$sentCount} sent, {$errorCount} errors");
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAccommodationNotificationEmails job failed: ' . $exception->getMessage());

        ActivityLog::logActivity(
            'email_notification',
            'job_failed',
            'system',
            null,
            [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ],
            "Échec du job d'envoi d'emails: {$exception->getMessage()}",
            'error'
        );
    }
}