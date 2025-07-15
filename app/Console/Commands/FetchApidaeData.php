<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApidaeService;

class FetchApidaeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidae:fetch {--test : Utiliser des données de test au lieu de l\'API} {--limit=150 : Limite du nombre d\'hébergements à récupérer} {--simple : Utiliser une requête simple sans critères}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupère les hébergements depuis l\'API Apidae';

    /**
     * Execute the console command.
     */
    public function handle(ApidaeService $apidaeService)
    {
        $isTest = $this->option('test');
        $limit = (int) $this->option('limit');
        $simple = $this->option('simple');

        if ($isTest) {
            return $this->handleTestData($apidaeService);
        }

        $this->info('🚀 Récupération des hébergements depuis Apidae…');

        try {
            // Vérifier la configuration
            $status = $apidaeService->getApiStatus();
            
            if (!$status['configured']) {
                $this->error('❌ Configuration API Apidae incomplète:');
                $this->line("  - API Key: " . ($status['api_key_set'] ? '✅' : '❌'));
                $this->line("  - Project ID: " . ($status['project_id_set'] ? '✅' : '❌'));
                $this->line("  - Selection ID: " . ($status['selection_id_set'] ? '✅' : '❌'));
                $this->info('💡 Utilisez --test pour tester avec des données de test');
                return 1;
            }

            $this->info("📋 Configuration utilisée:");
            $this->line("  - Project ID: " . $status['project_id']);
            $this->line("  - Selection ID: " . $status['selection_id']);
            $this->line("  - Limite: {$limit} hébergements");
            $this->line("  - Mode simple: " . ($simple ? 'Oui' : 'Non'));
            $this->line("");

            // Récupérer les données de l'API
            $this->info('📡 Appel de l\'API Apidae...');
            $apiData = $apidaeService->fetchAccommodations($limit, $simple);
            
            $this->info('📦 Nombre d\'hébergements reçus: ' . count($apiData));

            if (empty($apiData)) {
                $this->warn('⚠️  Aucune donnée reçue de l\'API');
                return 0;
            }

            // Traiter et sauvegarder les données
            $this->info('⚙️  Traitement des données...');
            $results = $apidaeService->processAndSaveAccommodations($apiData);

            // Afficher les résultats
            $this->displayResults($results);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());
            $this->info('💡 Utilisez --test pour tester avec des données de test');
            return 1;
        }
    }

    /**
     * Handle test data for development purposes
     */
    private function handleTestData(ApidaeService $apidaeService)
    {
        $this->info('🧪 Utilisation de données de test...');

        try {
            $testData = $apidaeService->getTestData();
            $this->info('📦 Nombre d\'hébergements de test: ' . count($testData));

            $results = $apidaeService->processAndSaveAccommodations($testData);

            $this->displayResults($results);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du traitement des données de test: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display processing results
     */
    private function displayResults(array $results): void
    {
        $this->line("");
        $this->info("✅ Opération terminée avec succès!");
        $this->line("┌─────────────────────────────────┐");
        $this->line("│ 📊 RÉSULTATS                   │");
        $this->line("├─────────────────────────────────┤");
        $this->line("│ 🆕 Créés: " . str_pad($results['created'], 18) . "│");
        $this->line("│ 🔄 Mis à jour: " . str_pad($results['updated'], 14) . "│");
        
        if ($results['errors'] > 0) {
            $this->line("│ ❌ Erreurs: " . str_pad($results['errors'], 16) . "│");
        }
        
        $this->line("│ 📈 Total traité: " . str_pad($results['total_processed'], 12) . "│");
        $this->line("└─────────────────────────────────┘");

        if ($results['errors'] > 0) {
            $this->warn("⚠️  {$results['errors']} erreur(s) détectée(s). Consultez les logs pour plus de détails.");
        }

        if ($results['total_processed'] > 0) {
            $this->info('🎉 Données mises à jour! Le cache a été vidé automatiquement.');
        }
    }
}
