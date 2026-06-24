<?php

namespace App\Console\Commands;

use App\Services\WeeklyReleaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseWeeklyVerificationPages extends Command
{
    protected $signature = 'verification:release-weekly';

    protected $description = 'Libère 2 pages par utilisateur (plafond strict) chaque dimanche nuit pour ne pas submerger les relecteurs.';

    public function handle(WeeklyReleaseService $service): int
    {
        $report = $service->releaseWeekly();

        if (empty($report)) {
            $this->info('Aucune libération nécessaire (tous les utilisateurs ont déjà ≥ 2 pages actives ou aucune assignation en attente).');
            return Command::SUCCESS;
        }

        $totalReleased = array_sum($report);
        $usersAffected = count($report);

        foreach ($report as $userId => $count) {
            $this->line("→ User #{$userId} : {$count} page(s) libérée(s).");
        }

        Log::channel('single')->info('[VERIFICATION CRON] Pages hebdomadaires libérées', [
            'total_released' => $totalReleased,
            'users_affected' => $usersAffected,
            'breakdown' => $report,
        ]);

        $this->info("{$totalReleased} page(s) libérée(s) pour {$usersAffected} utilisateur(s).");
        return Command::SUCCESS;
    }
}
