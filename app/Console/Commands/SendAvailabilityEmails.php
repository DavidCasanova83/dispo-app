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

        // Récupérer les hébergements avec email qui n'ont pas encore reçu d'email aujourd'hui
        $accommodations = Accommodation::whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($query) {
                $query->whereNull('email_sent_at')
                      ->orWhereDate('email_sent_at', '<', today());
            })
            ->get();

        if ($accommodations->isEmpty()) {
            $this->warn('⚠️  Aucun hébergement avec email trouvé.');
            return self::FAILURE;
        }

        // Regroupe par email (insensible casse/espaces) pour n'envoyer qu'un message par destinataire.
        $groups = $accommodations->groupBy(fn ($a) => strtolower(trim($a->email)));

        $this->info("📊 {$accommodations->count()} hébergement(s) regroupés en {$groups->count()} email(s)");
        $this->newLine();

        // Mode dry-run : afficher sans envoyer
        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode DRY-RUN activé - Aucun email ne sera envoyé');
            $this->newLine();

            $rows = [];
            foreach ($groups as $email => $group) {
                foreach ($group as $i => $acc) {
                    $rows[] = [
                        $acc->id,
                        $acc->name,
                        $email,
                        $i === 0 ? $group->count() : '',
                        $acc->status ?? 'N/A',
                    ];
                }
            }

            $this->table(['ID', 'Nom', 'Email', 'Group size', 'Statut'], $rows);

            $this->newLine();
            $this->info("✅ {$accommodations->count()} hébergement(s) regroupés en {$groups->count()} email(s) seraient envoyés en mode normal");
            return self::SUCCESS;
        }

        // Envoi réel des emails (un job par groupe d'email)
        $progressBar = $this->output->createProgressBar($groups->count());
        $progressBar->start();

        $dispatched = 0;
        foreach ($groups as $email => $group) {
            try {
                SendAccommodationAvailabilityEmail::dispatch(
                    (string) $email,
                    $group->pluck('id')->all()
                );
                $dispatched++;
            } catch (\Exception $e) {
                $this->error("\n❌ Erreur pour le groupe {$email} : {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("✅ {$accommodations->count()} hébergement(s) regroupés en {$dispatched} email(s) programmé(s) pour envoi");
        $this->comment('💡 Les emails seront traités par la queue dans la minute qui suit');
        $this->newLine();

        // Logs
        Log::info('Envoi automatique des emails de disponibilité', [
            'accommodations_total' => $accommodations->count(),
            'emails_dispatched' => $dispatched,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
