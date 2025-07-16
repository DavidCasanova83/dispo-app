<?php

namespace App\Console\Commands;

use App\Jobs\SendAccommodationNotificationEmails;
use Illuminate\Console\Command;

class SendAccommodationNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accommodation:send-notifications 
                            {--test : Test mode - send only one email}
                            {--email= : Send to specific email address only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification emails to accommodations with status update links';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Lancement de l\'envoi des notifications d\'hébergement...');

        if ($this->option('test')) {
            $this->info('Mode test activé - envoi d\'un seul email');
        }

        if ($this->option('email')) {
            $this->info('Envoi vers email spécifique: ' . $this->option('email'));
        }

        try {
            // Dispatch the job
            SendAccommodationNotificationEmails::dispatch();
            
            $this->info('✅ Job d\'envoi d\'emails dispatché avec succès!');
            $this->info('📧 Les emails seront traités par le système de queue.');
            $this->info('🔍 Consultez les logs pour suivre le progrès.');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du dispatch du job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
