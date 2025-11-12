<?php

namespace App\Console\Commands;

use App\Jobs\SendAccommodationAvailabilityEmail;
use App\Models\Accommodation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAvailabilityEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-availability
                            {--dry-run : Afficher les hébergements sans envoyer les emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie les emails de disponibilité à tous les hébergements avec email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Début de l\'envoi des emails de disponibilité...');
        $this->newLine();

        // Récupérer tous les hébergements avec email
        $accommodations = Accommodation::whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($accommodations->isEmpty()) {
            $this->warn('⚠️  Aucun hébergement avec email trouvé.');
            return self::FAILURE;
        }

        $this->info("📊 {$accommodations->count()} hébergement(s) avec email trouvé(s)");
        $this->newLine();

        // Mode dry-run : afficher sans envoyer
        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode DRY-RUN activé - Aucun email ne sera envoyé');
            $this->newLine();

            $this->table(
                ['ID', 'Nom', 'Email', 'Statut'],
                $accommodations->map(fn($acc) => [
                    $acc->id,
                    $acc->name,
                    $acc->email,
                    $acc->status ?? 'N/A'
                ])
            );

            $this->newLine();
            $this->info("✅ {$accommodations->count()} email(s) seraient envoyé(s) en mode normal");
            return self::SUCCESS;
        }

        // Envoi réel des emails
        $progressBar = $this->output->createProgressBar($accommodations->count());
        $progressBar->start();

        $sent = 0;
        foreach ($accommodations as $accommodation) {
            try {
                SendAccommodationAvailabilityEmail::dispatch($accommodation);
                $sent++;
            } catch (\Exception $e) {
                $this->error("\n❌ Erreur pour {$accommodation->name} : {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("✅ {$sent} email(s) programmé(s) pour envoi");
        $this->comment('💡 Les emails seront traités par la queue dans la minute qui suit');
        $this->newLine();

        // Logs
        Log::info('Envoi automatique des emails de disponibilité', [
            'total' => $accommodations->count(),
            'sent' => $sent,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
