<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $this->info('🔄 Déploiement Dispo-App...');

        // Nettoyer les caches
        $this->info('🧹 Nettoyage des caches...');
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');

        // Rebuild des assets (CSS, JS, etc.)
        $this->info('🎨 Rebuild des assets (CSS, JS, etc.)...');
        shell_exec('npm run build');

        // Optimiser
        $this->info('⚡ Optimisation...');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        

        $this->info('✅ Déploiement terminé!');
        
        return Command::SUCCESS;
    }
}
