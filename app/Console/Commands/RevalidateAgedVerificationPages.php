<?php

namespace App\Console\Commands;

use App\Models\VerificationPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RevalidateAgedVerificationPages extends Command
{
    protected $signature = 'verification:revalidate-aged {--days=365 : Âge maximal d\'une validation avant nouvelle vérification}';

    protected $description = 'Repasse en "à vérifier" les pages validées depuis plus d\'un an (365 jours par défaut).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        // La page est validated et a été clôturée annuellement il y a plus de $days jours.
        // (Les reviews ayant été purgées au moment de la clôture, on s'appuie sur validated_at,
        // pas sur la dernière review FR done.)
        $pagesToRevalidate = VerificationPage::query()
            ->where('status', 'validated')
            ->whereNotNull('validated_at')
            ->where('validated_at', '<', $threshold)
            ->get();

        if ($pagesToRevalidate->isEmpty()) {
            $this->info('Aucune page à réinitialiser.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pagesToRevalidate as $page) {
            $page->update([
                'status' => 'pending',
                'validated_at' => null,
            ]);
            $count++;
            $this->line("→ Page #{$page->id} \"{$page->title}\" repassée en 'à vérifier'.");
        }

        Log::channel('single')->info('[VERIFICATION CRON] Pages auto-renouvelées', [
            'count' => $count,
            'threshold_days' => $days,
            'page_ids' => $pagesToRevalidate->pluck('id')->toArray(),
        ]);

        $this->info("{$count} page(s) repassée(s) en 'à vérifier'.");
        return Command::SUCCESS;
    }
}
