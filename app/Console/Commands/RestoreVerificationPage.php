<?php

namespace App\Console\Commands;

use App\Models\VerificationPage;
use Illuminate\Console\Command;

/**
 * Contrepartie de la suppression douce : rend visible une page supprimée depuis
 * l'interface admin, avec ses relectures et ses assignations intactes.
 */
class RestoreVerificationPage extends Command
{
    protected $signature = 'verification:restore-page
        {id? : Identifiant de la page à restaurer}
        {--list : Liste les pages supprimées restaurables}';

    protected $description = 'Restaure une page de vérification supprimée (ou liste les pages supprimées).';

    public function handle(): int
    {
        $trashed = VerificationPage::onlyTrashed()->orderByDesc('deleted_at')->get();

        if ($this->option('list') || ! $this->argument('id')) {
            if ($trashed->isEmpty()) {
                $this->info('Aucune page supprimée.');

                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Titre', 'Supprimée le', 'Relectures', 'Assignations'],
                $trashed->map(fn ($p) => [
                    $p->id,
                    mb_substr($p->title, 0, 45),
                    $p->deleted_at->format('d/m/Y H:i'),
                    $p->reviews()->count(),
                    $p->assignees()->count(),
                ])->all()
            );

            if (! $this->argument('id')) {
                $this->newLine();
                $this->line('Restaurez-en une avec : php artisan verification:restore-page {id}');
            }

            return self::SUCCESS;
        }

        $page = VerificationPage::onlyTrashed()->find($this->argument('id'));

        if (! $page) {
            $this->error("Aucune page supprimée avec l'identifiant {$this->argument('id')}.");

            return self::FAILURE;
        }

        $page->restore();

        $this->info(sprintf(
            'Page #%d "%s" restaurée, avec %d relecture(s) et %d assignation(s).',
            $page->id,
            $page->title,
            $page->reviews()->count(),
            $page->assignees()->count()
        ));

        return self::SUCCESS;
    }
}
