<?php

namespace App\Console\Commands;

use App\Models\Accommodation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetAccommodationStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accommodations:reset-status
                            {--dry-run : Afficher les hébergements sans réinitialiser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise tous les statuts des hébergements à "en_attente"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Réinitialisation des statuts des hébergements...');
        $this->newLine();

        // Compter les hébergements à réinitialiser (ceux qui ne sont pas déjà en_attente)
        $accommodationsToReset = Accommodation::where('status', '!=', 'en_attente')->get();
        $totalAccommodations = Accommodation::count();

        $this->info("📊 {$accommodationsToReset->count()} hébergement(s) à réinitialiser sur {$totalAccommodations} total");
        $this->newLine();

        if ($accommodationsToReset->isEmpty()) {
            $this->info('✅ Tous les hébergements sont déjà en attente.');
            return self::SUCCESS;
        }

        // Mode dry-run : afficher sans modifier
        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();

            $this->table(
                ['ID', 'Nom', 'Statut actuel'],
                $accommodationsToReset->map(fn($acc) => [
                    $acc->id,
                    $acc->name,
                    $acc->status,
                ])
            );

            $this->newLine();
            $this->info("✅ {$accommodationsToReset->count()} hébergement(s) seraient réinitialisé(s) en mode normal");
            return self::SUCCESS;
        }

        // Réinitialisation effective
        $updated = Accommodation::where('status', '!=', 'en_attente')
            ->update(['status' => 'en_attente']);

        $this->info("✅ {$updated} hébergement(s) réinitialisé(s) à 'en_attente'");
        $this->newLine();

        // Logs
        Log::info('Réinitialisation automatique des statuts des hébergements', [
            'updated' => $updated,
            'total' => $totalAccommodations,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
