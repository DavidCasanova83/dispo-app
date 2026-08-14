<?php

namespace App\Console\Commands;

use App\Models\VerificationPage;
use App\Services\VerificationReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Réaligne le statut des pages sur la règle de calcul courante.
 *
 * Nécessaire après un changement de règle : les statuts ne sont normalement
 * recalculés qu'à la soumission d'une relecture, à son traitement, ou à un
 * changement d'assignation — une page inactive garderait donc indéfiniment
 * un statut calculé avec l'ancienne règle.
 *
 * N'écrit QUE la colonne `status` de `verification_pages`. Aucune relecture,
 * aucune assignation n'est lue en écriture.
 */
class RecomputeVerificationStatuses extends Command
{
    protected $signature = 'verification:recompute-statuses
        {--apply : Applique les changements (sans cette option, la commande se contente de simuler)}';

    protected $description = 'Recalcule le statut des pages de vérification. Simule par défaut ; utilisez --apply pour écrire.';

    public function handle(VerificationReviewService $service): int
    {
        $apply = (bool) $this->option('apply');

        // Les pages clôturées sont hors du calcul : leur statut est piloté par
        // closeAnnualVerification() et le cron de renouvellement.
        $pages = VerificationPage::where('status', '!=', 'validated')
            ->with(['assignees', 'reviews'])
            ->get();

        $this->info(sprintf('%d page(s) examinée(s)%s.', $pages->count(), $apply ? '' : ' — SIMULATION'));

        $changes = [];

        foreach ($pages as $page) {
            $before = $page->status;
            $after = $this->predict($page);

            if ($before !== $after) {
                $changes[] = [$page->id, mb_substr($page->title, 0, 45), $before, $after];
            }
        }

        if (empty($changes)) {
            $this->info('Tous les statuts sont déjà à jour, rien à faire.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(['ID', 'Titre', 'Avant', 'Après'], $changes);
        $this->newLine();

        if (! $apply) {
            $this->warn(sprintf(
                '%d page(s) changeraient de statut. Relancez avec --apply pour écrire.',
                count($changes)
            ));

            return self::SUCCESS;
        }

        if (! $this->confirm(sprintf('Appliquer ces %d changement(s) de statut ?', count($changes)), false)) {
            $this->info('Annulé, aucune écriture.');

            return self::SUCCESS;
        }

        foreach ($changes as [$id, , , ]) {
            $service->refreshPageStatus(VerificationPage::find($id));
        }

        Log::channel('single')->info('[VERIFICATION] Réalignement des statuts de page', [
            'count' => count($changes),
            'changes' => array_map(fn ($c) => ['id' => $c[0], 'from' => $c[2], 'to' => $c[3]], $changes),
        ]);

        $this->info(sprintf('%d statut(s) mis à jour.', count($changes)));

        return self::SUCCESS;
    }

    /**
     * Rejoue la règle de recomputePageStatus() sans rien écrire, à partir des
     * relations déjà chargées (évite 2 requêtes par page sur tout le catalogue).
     */
    private function predict(VerificationPage $page): string
    {
        $assigneeIds = $page->assignees->pluck('id');

        if ($assigneeIds->isEmpty()) {
            return 'pending';
        }

        $active = $page->reviews
            ->where('language', 'fr')
            ->whereIn('user_id', $assigneeIds)
            ->where('status', '!=', 'revision_requested');

        if ($active->isEmpty()) {
            return 'pending';
        }

        if ($active->contains(fn ($r) => $r->status === 'pending_admin')) {
            return 'needs_fix';
        }

        if ($active->contains(fn ($r) => $r->status === 'in_progress')) {
            return 'in_progress';
        }

        $allDone = $active->every(fn ($r) => $r->status === 'done');
        $allAssigneesReviewed = $active->pluck('user_id')->unique()->count() === $assigneeIds->count();

        return ($allDone && $allAssigneesReviewed) ? 'awaiting_validation' : 'pending';
    }
}
