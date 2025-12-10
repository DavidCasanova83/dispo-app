<?php

namespace App\Console\Commands;

use App\Models\Agenda;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActivatePendingAgendas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agendas:activate-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Active les agendas en attente dont la date de début est atteinte';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();

        Log::info('[AGENDA CRON] Démarrage de la vérification des agendas', [
            'date' => $today->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
        ]);

        // Trouver l'agenda en attente dont la date de début est <= aujourd'hui
        $pendingAgenda = Agenda::pending()
            ->where('start_date', '<=', $today)
            ->first();

        if (!$pendingAgenda) {
            Log::info('[AGENDA CRON] Aucun agenda en attente à activer');
            $this->info('Aucun agenda en attente à activer.');
            return Command::SUCCESS;
        }

        Log::info('[AGENDA CRON] Agenda en attente trouvé - début du processus d\'activation', [
            'agenda_id' => $pendingAgenda->id,
            'title' => $pendingAgenda->title,
            'start_date' => $pendingAgenda->start_date->format('Y-m-d'),
            'end_date' => $pendingAgenda->end_date->format('Y-m-d'),
            'pdf_path' => $pendingAgenda->pdf_path,
        ]);

        $this->info("Activation de l'agenda: {$pendingAgenda->title} (ID: {$pendingAgenda->id})");

        // 1. Archiver l'agenda en cours actuel (s'il existe)
        $currentAgenda = Agenda::current()->first();
        $archivedAgendaId = null;

        if ($currentAgenda) {
            $archivedAgendaId = $currentAgenda->id;
            $this->archiveAgenda($currentAgenda);
            $this->info("Agenda archivé: {$currentAgenda->title} (ID: {$currentAgenda->id})");
        } else {
            Log::info('[AGENDA CRON] Aucun agenda en cours à archiver');
        }

        // 2. Activer l'agenda en attente
        $this->activateAgenda($pendingAgenda);
        $this->info("Agenda activé avec succès!");

        Log::info('[AGENDA CRON] ✅ Processus terminé avec succès', [
            'activated_agenda_id' => $pendingAgenda->id,
            'activated_agenda_title' => $pendingAgenda->title,
            'archived_agenda_id' => $archivedAgendaId,
            'activation_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Archiver un agenda
     */
    private function archiveAgenda(Agenda $agenda): void
    {
        $disk = Storage::disk('public');

        Log::info('[AGENDA CRON] Début archivage de l\'agenda en cours', [
            'agenda_id' => $agenda->id,
            'title' => $agenda->title,
            'start_date' => $agenda->start_date->format('Y-m-d'),
            'end_date' => $agenda->end_date->format('Y-m-d'),
        ]);

        // Générer le nom du fichier archive basé sur les dates
        $startDate = $agenda->start_date->format('Y-m-d');
        $endDate = $agenda->end_date->format('Y-m-d');
        $archivePath = "agendas/archives/{$startDate}_{$endDate}.pdf";

        // S'assurer que le dossier archives existe
        if (!$disk->exists('agendas/archives')) {
            $disk->makeDirectory('agendas/archives');
            Log::info('[AGENDA CRON] Dossier archives créé');
        }

        // Copier le PDF actuel vers les archives
        if ($disk->exists('agendas/agenda-en-cours.pdf')) {
            $disk->copy('agendas/agenda-en-cours.pdf', $archivePath);
            Log::info('[AGENDA CRON] PDF copié vers les archives', [
                'source' => 'agendas/agenda-en-cours.pdf',
                'destination' => $archivePath,
            ]);
        } else {
            Log::warning('[AGENDA CRON] ⚠️ Fichier PDF source introuvable pour archivage', [
                'expected_path' => 'agendas/agenda-en-cours.pdf',
            ]);
        }

        // Mettre à jour le statut de l'agenda
        $agenda->update([
            'status' => Agenda::STATUS_ARCHIVED,
            'archived_at' => now(),
            'pdf_path' => $archivePath,
        ]);

        Log::info('[AGENDA CRON] Agenda archivé en BDD', [
            'agenda_id' => $agenda->id,
            'new_status' => Agenda::STATUS_ARCHIVED,
            'pdf_path' => $archivePath,
        ]);
    }

    /**
     * Activer un agenda en attente
     */
    private function activateAgenda(Agenda $agenda): void
    {
        $disk = Storage::disk('public');

        Log::info('[AGENDA CRON] Début activation du nouvel agenda', [
            'agenda_id' => $agenda->id,
            'title' => $agenda->title,
            'pdf_path' => $agenda->pdf_path,
        ]);

        // Copier le PDF pending vers agenda-en-cours.pdf
        if ($agenda->pdf_path && $disk->exists($agenda->pdf_path)) {
            $disk->copy($agenda->pdf_path, 'agendas/agenda-en-cours.pdf');
            Log::info('[AGENDA CRON] PDF copié depuis pending vers agenda-en-cours', [
                'source' => $agenda->pdf_path,
                'destination' => 'agendas/agenda-en-cours.pdf',
            ]);

            // Supprimer le fichier pending
            $disk->delete($agenda->pdf_path);
            Log::info('[AGENDA CRON] Fichier pending supprimé', [
                'deleted_file' => $agenda->pdf_path,
            ]);
        } else {
            Log::warning('[AGENDA CRON] ⚠️ Fichier PDF pending introuvable', [
                'expected_path' => $agenda->pdf_path,
            ]);
        }

        // Mettre à jour le statut et le chemin du PDF
        $agenda->update([
            'status' => Agenda::STATUS_CURRENT,
            'pdf_path' => 'agendas/agenda-en-cours.pdf',
        ]);

        Log::info('[AGENDA CRON] Agenda activé en BDD', [
            'agenda_id' => $agenda->id,
            'new_status' => Agenda::STATUS_CURRENT,
            'pdf_path' => 'agendas/agenda-en-cours.pdf',
        ]);
    }
}
