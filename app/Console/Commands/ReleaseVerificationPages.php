<?php

namespace App\Console\Commands;

use App\Services\PageReleaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseVerificationPages extends Command
{
    /**
     * Pas de cadence dans le nom : la fréquence est définie dans routes/console.php.
     * Le renommer à chaque changement de rythme ferait dériver la documentation.
     */
    protected $signature = 'verification:release-pages';

    protected $description = 'Complète les pages actives de chaque relecteur jusqu\'au plafond, pour ne pas le submerger.';

    public function handle(PageReleaseService $service): int
    {
        $report = $service->replenishAll();
        $quota = PageReleaseService::ACTIVE_PAGES_QUOTA;

        if (empty($report)) {
            $this->info("Aucune libération nécessaire (tous les relecteurs ont déjà ≥ {$quota} pages actives, ou aucune assignation en attente).");
            return Command::SUCCESS;
        }

        $totalReleased = array_sum($report);
        $usersAffected = count($report);

        foreach ($report as $userId => $count) {
            $this->line("→ User #{$userId} : {$count} page(s) libérée(s).");
        }

        Log::channel('single')->info('[VERIFICATION CRON] Pages libérées', [
            'total_released' => $totalReleased,
            'users_affected' => $usersAffected,
            'quota' => $quota,
            'breakdown' => $report,
        ]);

        $this->info("{$totalReleased} page(s) libérée(s) pour {$usersAffected} relecteur(s).");

        return Command::SUCCESS;
    }
}
