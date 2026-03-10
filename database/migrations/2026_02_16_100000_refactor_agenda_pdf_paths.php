<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Migrer les fichiers PDF existants vers le nouveau schéma : agendas/{id}.pdf
     * Chaque agenda garde son propre fichier unique, plus de copie entre dossiers.
     */
    public function up(): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists('agendas')) {
            $disk->makeDirectory('agendas');
        }

        $agendas = DB::table('agendas')->whereNull('deleted_at')->get();

        foreach ($agendas as $agenda) {
            $newPath = "agendas/{$agenda->id}.pdf";
            $oldPath = $agenda->pdf_path;

            // Déjà au bon chemin
            if ($oldPath === $newPath) {
                continue;
            }

            // Fichier source introuvable
            if (!$oldPath || !$disk->exists($oldPath)) {
                Log::warning("[MIGRATION] PDF introuvable pour agenda {$agenda->id}: {$oldPath}");
                DB::table('agendas')
                    ->where('id', $agenda->id)
                    ->update(['pdf_path' => $newPath]);
                continue;
            }

            // Copier vers le nouveau chemin
            $disk->copy($oldPath, $newPath);

            if ($disk->exists($newPath)) {
                DB::table('agendas')
                    ->where('id', $agenda->id)
                    ->update(['pdf_path' => $newPath]);

                // Supprimer l'ancien fichier (sauf agenda-en-cours.pdf, traité à la fin)
                if ($oldPath !== 'agendas/agenda-en-cours.pdf') {
                    $disk->delete($oldPath);
                }

                Log::info("[MIGRATION] Agenda {$agenda->id}: {$oldPath} -> {$newPath}");
            } else {
                Log::error("[MIGRATION] Copie échouée pour agenda {$agenda->id}: {$oldPath} -> {$newPath}");
            }
        }

        // Backup de l'ancien fichier fixe
        if ($disk->exists('agendas/agenda-en-cours.pdf')) {
            $disk->move('agendas/agenda-en-cours.pdf', 'agendas/agenda-en-cours-backup.pdf');
            Log::info('[MIGRATION] agenda-en-cours.pdf sauvegardé en backup');
        }
    }

    public function down(): void
    {
        // Restaurer le backup si disponible
        $disk = Storage::disk('public');
        if ($disk->exists('agendas/agenda-en-cours-backup.pdf')) {
            $disk->move('agendas/agenda-en-cours-backup.pdf', 'agendas/agenda-en-cours.pdf');
        }

        Log::warning('[MIGRATION] Rollback de la migration des chemins PDF. Les anciens chemins (pending/, archives/) ne sont pas restaurés automatiquement.');
    }
};
