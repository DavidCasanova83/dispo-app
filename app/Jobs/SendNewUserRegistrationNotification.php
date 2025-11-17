<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendNewUserRegistrationNotification implements ShouldQueue
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

        // Récupère tous les super-admins (utilisateurs avec la permission 'manage-users')
        $superAdmins = User::permission('manage-users')->get();

        if ($superAdmins->isEmpty()) {
            Log::warning("No super-admins found to notify about new user registration", [
                'new_user_id' => $this->user->id,
            ]);
            return;
        }

        // Envoie un email à chaque super-admin
        $successCount = 0;
        $errorCount = 0;

        foreach ($superAdmins as $admin) {
            if (empty($admin->email)) {
                Log::warning("Super-admin {$admin->id} has no email address");
                continue;
            }

            $result = $mailjetService->sendNewUserNotification(
                $admin->email,
                $admin->name,
                $this->user
            );

            if ($result['success']) {
                $successCount++;
                Log::info("New user registration notification sent to admin {$admin->id}", [
                    'admin_email' => $admin->email,
                    'new_user_id' => $this->user->id,
                    'new_user_email' => $this->user->email,
                ]);
            } else {
                $errorCount++;
                Log::error("Failed to send new user registration notification to admin {$admin->id}", [
                    'admin_email' => $admin->email,
                    'new_user_id' => $this->user->id,
                    'error' => $result['error'],
                ]);
            }
        }

        Log::info("New user registration notifications sent", [
            'new_user_id' => $this->user->id,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_admins' => $superAdmins->count(),
        ]);
    }
}
