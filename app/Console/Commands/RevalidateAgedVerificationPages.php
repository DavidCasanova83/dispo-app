<?php

namespace App\Console\Commands;

use App\Models\VerificationPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevalidateAgedVerificationPages extends Command
{
    protected $signature = 'verification:revalidate-aged {--days= : Âge maximal d\'une validation avant nouvelle vérification (défaut : VerificationPage::REVALIDATION_DAYS)}';

    protected $description = 'Repasse en "à vérifier" les pages validées depuis plus d\'un an (365 jours par défaut).';

    public function handle(): int
    {
        // Même source que l'affichage « prochaine vérification » côté admin :
        // les deux doivent annoncer la même date.
        // Comparaison à null et non ?: — « --days=0 » (tout renouveler maintenant)
        // est une valeur légitime, que ?: écraserait silencieusement par le défaut.
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : VerificationPage::REVALIDATION_DAYS;
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
        $requeued = 0;

        foreach ($pagesToRevalidate as $page) {
            DB::transaction(function () use ($page, &$count, &$requeued) {
                $page->update([
                    'status' => 'pending',
                    'validated_at' => null,
                ]);

                // Remettre les assignations en file d'attente.
                //
                // La clôture annuelle conserve les assignations, released_at compris.
                // Sans cette remise à zéro, toutes les pages renouvelées le même jour
                // réapparaissaient d'un coup sur le dashboard du relecteur : les trois
                // conditions de scopePendingForUser étaient satisfaites immédiatement
                // (assignation libérée, statut ≠ validated, aucune review — purgées à
                // la clôture). Le plafond de pages actives était donc contourné.
                //
                // Remis à NULL, le cycle repart par la distribution nocturne, qui les
                // ressort au compte-gouttes selon priorité puis deadline.
                $requeued += DB::table('verification_assignments')
                    ->where('page_id', $page->id)
                    ->whereNotNull('released_at')
                    ->update(['released_at' => null]);

                $count++;
            });

            $this->line("→ Page #{$page->id} \"{$page->title}\" repassée en 'à vérifier'.");
        }

        Log::channel('single')->info('[VERIFICATION CRON] Pages auto-renouvelées', [
            'count' => $count,
            'assignments_requeued' => $requeued,
            'threshold_days' => $days,
            'page_ids' => $pagesToRevalidate->pluck('id')->toArray(),
        ]);

        $this->info("{$count} page(s) repassée(s) en 'à vérifier', {$requeued} assignation(s) remise(s) en file d'attente.");

        return Command::SUCCESS;
    }
}
