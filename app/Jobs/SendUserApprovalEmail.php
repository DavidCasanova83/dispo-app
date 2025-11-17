<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendUserApprovalEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MailjetService $mailjetService): void
    {
        // Vérifie que l'utilisateur a une adresse email
        if (empty($this->user->email)) {
            Log::warning("User {$this->user->id} has no email address");
            return;
        }

        // Vérifie que l'utilisateur est bien approuvé
        if (!$this->user->isApproved()) {
            Log::warning("User {$this->user->id} is not approved, skipping email");
            return;
        }

        // Envoie l'email via Mailjet
        $result = $mailjetService->sendUserApprovalEmail(
            $this->user->email,
            $this->user->name
        );

        // Log du résultat
        if ($result['success']) {
            Log::info("User approval email sent to user {$this->user->id}", [
                'user_email' => $this->user->email,
                'user_name' => $this->user->name,
            ]);
        } else {
            Log::error("Failed to send user approval email to user {$this->user->id}", [
                'user_email' => $this->user->email,
                'error' => $result['error'],
            ]);
        }
    }
}
