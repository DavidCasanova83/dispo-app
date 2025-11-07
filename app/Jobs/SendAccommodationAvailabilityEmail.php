<?php

namespace App\Jobs;

use App\Models\Accommodation;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAccommodationAvailabilityEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Accommodation $accommodation
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MailjetService $mailjetService): void
    {
        // Vérifie que l'hébergement a une adresse email
        if (empty($this->accommodation->email)) {
            Log::warning("Accommodation {$this->accommodation->id} has no email address");
            return;
        }

        // Génère un token unique pour ce processus d'envoi
        $token = $this->accommodation->generateResponseToken();

        // Génère les URLs de réponse
        $availableUrl = route('accommodation.response', [
            'token' => $token,
            'available' => 1,
        ]);

        $notAvailableUrl = route('accommodation.response', [
            'token' => $token,
            'available' => 0,
        ]);

        // Envoie l'email via Mailjet
        $result = $mailjetService->sendAvailabilityRequest(
            $this->accommodation->email,
            $this->accommodation->name,
            $this->accommodation->name,
            $availableUrl,
            $notAvailableUrl
        );

        // Si l'envoi réussit, marque l'hébergement comme ayant reçu l'email
        if ($result['success']) {
            $this->accommodation->markEmailSent();
            Log::info("Availability email sent to accommodation {$this->accommodation->id}");
        } else {
            Log::error("Failed to send availability email to accommodation {$this->accommodation->id}", [
                'error' => $result['error'],
            ]);
        }
    }
}
