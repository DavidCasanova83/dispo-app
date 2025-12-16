<?php

namespace App\Jobs;

use App\Models\Agenda;
use App\Models\User;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendNewAgendaNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Agenda $agenda
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MailjetService $mailjetService): void
    {
        // Récupère tous les super-admins (utilisateurs avec la permission 'manage-users')
        $superAdmins = User::permission('manage-users')->get();

        if ($superAdmins->isEmpty()) {
            Log::warning("No super-admins found to notify about new agenda", [
                'agenda_id' => $this->agenda->id,
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

            $result = $mailjetService->sendNewAgendaNotification(
                $admin->email,
                $admin->name,
                $this->agenda
            );

            if ($result['success']) {
                $successCount++;
                Log::info("New agenda notification sent to admin {$admin->id}", [
                    'admin_email' => $admin->email,
                    'agenda_id' => $this->agenda->id,
                    'agenda_title' => $this->agenda->title,
                ]);
            } else {
                $errorCount++;
                Log::error("Failed to send new agenda notification to admin {$admin->id}", [
                    'admin_email' => $admin->email,
                    'agenda_id' => $this->agenda->id,
                    'error' => $result['error'],
                ]);
            }
        }

        Log::info("New agenda notifications sent", [
            'agenda_id' => $this->agenda->id,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_admins' => $superAdmins->count(),
        ]);
    }
}
