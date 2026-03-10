<?php

namespace App\Jobs;

use App\Models\Accommodation;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SendAccommodationAvailabilityEmail implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $uniqueFor = 86400;

    public function uniqueId(): string
    {
        return 'accommodation-email-' . $this->accommodation->id;
    }

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
        // Vérifier si l'email a déjà été envoyé aujourd'hui
        $this->accommodation->refresh();
        if ($this->accommodation->email_sent_at && $this->accommodation->email_sent_at->isToday()) {
            Log::info("Email already sent today for accommodation {$this->accommodation->id}, skipping");
            return;
        }

        // Vérifie que l'hébergement a une adresse email
        if (empty($this->accommodation->email)) {
            Log::warning("Accommodation {$this->accommodation->id} has no email address");
            return;
        }

        // Génère les URLs de réponse signées avec expiration à 7 jours
        $availableUrl = URL::temporarySignedRoute('accommodation.response', now()->addDays(7), [
            'accommodation' => $this->accommodation->id,
            'available' => 1,
        ]);

        $notAvailableUrl = URL::temporarySignedRoute('accommodation.response', now()->addDays(7), [
            'accommodation' => $this->accommodation->id,
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
