<?php

namespace App\Console\Commands;

use App\Models\VerificationPage;
use App\Models\VerificationReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remplace l'ancien bouton « Tout supprimer » de l'interface admin, qui exposait
 * une suppression définitive et en cascade à un simple double-clic.
 *
 * La commande impose un décompte explicite de ce qui va disparaître et une
 * double confirmation en console. Elle n'est pas destinée à l'usage courant.
 */
class PurgeVerificationPages extends Command
{
    protected $signature = 'verification:purge {--force : Ne pas demander de confirmation (scripts uniquement)}';

    protected $description = 'Supprime DÉFINITIVEMENT toutes les pages de vérification, leurs assignations et leurs relectures.';

    public function handle(): int
    {
        // withTrashed : la purge doit aussi emporter les pages en suppression douce.
        $pages = VerificationPage::withTrashed()->count();
        $reviews = VerificationReview::count();
        $assignments = DB::table('verification_assignments')->count();

        if ($pages === 0 && $reviews === 0 && $assignments === 0) {
            $this->info('Rien à supprimer : le module de vérification est déjà vide.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('  SUPPRESSION DÉFINITIVE — action irréversible  ');
        $this->newLine();
        $this->table(['Table', 'Lignes supprimées'], [
            ['Pages à vérifier', $pages],
            ['Assignations', $assignments],
            ['Relectures', $reviews],
        ]);
        $this->warn('Aucune sauvegarde n\'est effectuée par cette commande.');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Confirmez-vous la suppression de toutes ces données ?', false)) {
                $this->info('Annulé, rien n\'a été supprimé.');

                return self::SUCCESS;
            }

            $typed = (string) $this->ask('Tapez SUPPRIMER en majuscules pour confirmer définitivement');

            if ($typed !== 'SUPPRIMER') {
                $this->info('Confirmation incorrecte, rien n\'a été supprimé.');

                return self::SUCCESS;
            }
        }

        // forceDelete : contourne les SoftDeletes pour émettre un vrai DELETE, seul
        // capable de déclencher les cascades sur assignations et relectures.
        DB::transaction(fn () => VerificationPage::withTrashed()->forceDelete());

        Log::channel('single')->warning('[VERIFICATION] Purge complète du module', [
            'pages' => $pages,
            'assignments' => $assignments,
            'reviews' => $reviews,
            'user_id' => auth()->id(),
        ]);

        $this->newLine();
        $this->info("Purge terminée : {$pages} page(s), {$assignments} assignation(s), {$reviews} relecture(s) supprimées.");

        return self::SUCCESS;
    }
}
