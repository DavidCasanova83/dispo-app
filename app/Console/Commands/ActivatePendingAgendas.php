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
    protected $description = 'Active les agendas en attente dont la date de début est atteinte et archive les agendas expirés';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $disk = Storage::disk('public');

        Log::channel('single')->info('[AGENDA CRON] ========== DÉMARRAGE ==========', [
            'date' => $today->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
        ]);

        // État initial pour debug
        $allAgendas = Agenda::withoutTrashed()->get();
        Log::channel('single')->info('[AGENDA CRON] État initial des agendas', [
            'total' => $allAgendas->count(),
            'current' => $allAgendas->where('status', 'current')->pluck('id')->toArray(),
            'pending' => $allAgendas->where('status', 'pending')->pluck('id')->toArray(),
            'archived' => $allAgendas->where('status', 'archived')->count(),
        ]);

        // 1. Auto-archiver les agendas en cours dont la date de fin est dépassée
        $expiredAgendas = Agenda::current()
            ->where('end_date', '<', $today)
            ->get();

        foreach ($expiredAgendas as $expired) {
            $this->archiveAgenda($expired);
            $this->info("Agenda expiré archivé: {$expired->title} (ID: {$expired->id})");
        }

        // 2. Trouver l'agenda en attente dont la date de début est <= aujourd'hui
        $pendingAgenda = Agenda::pending()
            ->where('start_date', '<=', $today)
            ->first();

        if (!$pendingAgenda) {
            Log::channel('single')->info('[AGENDA CRON] Aucun agenda en attente à activer');
            $this->info('Aucun agenda en attente à activer.');
            return Command::SUCCESS;
        }

        // Vérification du fichier PDF pending AVANT toute action
        $pendingPdfExists = $pendingAgenda->pdf_path && $disk->exists($pendingAgenda->pdf_path);
        $pendingPdfSize = $pendingPdfExists ? $disk->size($pendingAgenda->pdf_path) : 0;

        Log::channel('single')->info('[AGENDA CRON] Agenda en attente trouvé', [
            'agenda_id' => $pendingAgenda->id,
            'title' => $pendingAgenda->title,
            'start_date' => $pendingAgenda->start_date->format('Y-m-d'),
            'end_date' => $pendingAgenda->end_date->format('Y-m-d'),
            'pdf_path' => $pendingAgenda->pdf_path,
            'pdf_existe_sur_disque' => $pendingPdfExists,
            'pdf_taille_octets' => $pendingPdfSize,
            'category_id' => $pendingAgenda->category_id,
            'author_id' => $pendingAgenda->author_id,
        ]);

        $this->info("Activation de l'agenda: {$pendingAgenda->title} (ID: {$pendingAgenda->id})");

        // 3. Archiver l'agenda en cours actuel (s'il existe)
        $currentAgenda = Agenda::current()->first();

        if ($currentAgenda) {
            Log::channel('single')->info('[AGENDA CRON] Héritage catégorie/auteur', [
                'current_category_id' => $currentAgenda->category_id,
                'current_author_id' => $currentAgenda->author_id,
                'pending_category_id_avant' => $pendingAgenda->category_id,
                'pending_author_id_avant' => $pendingAgenda->author_id,
            ]);

            // Hériter catégorie/auteur si non définis sur le nouvel agenda
            if (!$pendingAgenda->category_id) {
                $pendingAgenda->category_id = $currentAgenda->category_id;
            }
            if (!$pendingAgenda->author_id) {
                $pendingAgenda->author_id = $currentAgenda->author_id;
            }

            Log::channel('single')->info('[AGENDA CRON] Après héritage', [
                'pending_category_id_apres' => $pendingAgenda->category_id,
                'pending_author_id_apres' => $pendingAgenda->author_id,
            ]);

            $this->archiveAgenda($currentAgenda);
            $this->info("Agenda archivé: {$currentAgenda->title} (ID: {$currentAgenda->id})");
        } else {
            Log::channel('single')->info('[AGENDA CRON] Aucun agenda en cours à archiver');
        }

        // 4. Activer l'agenda en attente
        $this->activateAgenda($pendingAgenda);
        $this->info("Agenda activé avec succès!");

        // Vérification finale
        $newCurrent = Agenda::current()->first();
        Log::channel('single')->info('[AGENDA CRON] ========== VÉRIFICATION FINALE ==========', [
            'nouvel_agenda_courant_id' => $newCurrent?->id,
            'nouvel_agenda_courant_titre' => $newCurrent?->title,
            'nouvel_agenda_pdf_path' => $newCurrent?->pdf_path,
            'pdf_existe_sur_disque' => $newCurrent?->pdf_path ? $disk->exists($newCurrent->pdf_path) : false,
            'pdf_taille_octets' => $newCurrent?->pdf_path && $disk->exists($newCurrent->pdf_path) ? $disk->size($newCurrent->pdf_path) : 0,
            'category_id' => $newCurrent?->category_id,
            'author_id' => $newCurrent?->author_id,
            'activation_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Archiver un agenda (opération BDD uniquement, pas de copie de fichier)
     */
    private function archiveAgenda(Agenda $agenda): void
    {
        Log::channel('single')->info('[AGENDA CRON] Archivage de l\'agenda', [
            'agenda_id' => $agenda->id,
            'title' => $agenda->title,
            'pdf_path' => $agenda->pdf_path,
        ]);

        $agenda->update([
            'status' => Agenda::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        Log::channel('single')->info('[AGENDA CRON] Agenda archivé en BDD', [
            'agenda_id' => $agenda->id,
            'nouveau_statut' => $agenda->fresh()->status,
        ]);
    }

    /**
     * Activer un agenda en attente (opération BDD uniquement, pas de copie de fichier)
     */
    private function activateAgenda(Agenda $agenda): void
    {
        $disk = Storage::disk('public');

        Log::channel('single')->info('[AGENDA CRON] Activation du nouvel agenda', [
            'agenda_id' => $agenda->id,
            'title' => $agenda->title,
            'pdf_path' => $agenda->pdf_path,
            'pdf_existe' => $agenda->pdf_path ? $disk->exists($agenda->pdf_path) : false,
        ]);

        // Vérifier que le fichier PDF existe bien
        if (!$agenda->pdf_path || !$disk->exists($agenda->pdf_path)) {
            Log::channel('single')->error('[AGENDA CRON] ERREUR CRITIQUE: Fichier PDF introuvable lors de l\'activation', [
                'agenda_id' => $agenda->id,
                'expected_path' => $agenda->pdf_path,
                'fichiers_dans_agendas' => $disk->files('agendas'),
            ]);
        }

        $agenda->update([
            'status' => Agenda::STATUS_CURRENT,
            'category_id' => $agenda->category_id,
            'author_id' => $agenda->author_id,
        ]);

        // Vérification post-update
        $fresh = $agenda->fresh();
        Log::channel('single')->info('[AGENDA CRON] Agenda activé en BDD - vérification', [
            'agenda_id' => $fresh->id,
            'statut_en_bdd' => $fresh->status,
            'pdf_path_en_bdd' => $fresh->pdf_path,
            'category_id_en_bdd' => $fresh->category_id,
            'author_id_en_bdd' => $fresh->author_id,
        ]);
    }
}
