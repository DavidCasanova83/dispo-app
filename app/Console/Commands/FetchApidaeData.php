<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Accommodation;
use Carbon\Carbon;

class FetchApidaeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidae:fetch {--test : Utiliser des données de test au lieu de l\'API} {--limit=50 : Limite du nombre d\'hébergements à récupérer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupère les hébergements depuis l\'API Apidae';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('test')) {
            return $this->handleTestData();
        }

        $this->info('Récupération des hébergements depuis Apidae…');

        // Vérifier que les variables d'environnement sont définies
        if (!env('APIDAE_API_KEY') || !env('APIDAE_PROJECT_ID') || !env('APIDAE_SELECTION_ID')) {
            $this->error('Variables d\'environnement manquantes. Vérifiez APIDAE_API_KEY, APIDAE_PROJECT_ID et APIDAE_SELECTION_ID dans votre fichier .env');
            $this->info('Utilisez --test pour tester avec des données de test');
            return 1;
        }

        try {
            $limit = $this->option('limit');

            $this->info("Configuration utilisée :");
            $this->line("  - Project ID: " . env('APIDAE_PROJECT_ID'));
            $this->line("  - Selection ID: " . env('APIDAE_SELECTION_ID'));
            $this->line("  - Limite: {$limit} hébergements");
            $this->line("");

            $response = Http::timeout(60)->post('https://api.apidae-tourisme.com/api/v002/objet-touristique/list-objets-touristiques', [
                'apiKey' => env('APIDAE_API_KEY'),
                'projectId' => env('APIDAE_PROJECT_ID'),
                'selectionIds' => [env('APIDAE_SELECTION_ID')],
                'projetId' => env('APIDAE_PROJECT_ID'),
                'query' => [
                    "criterias" => [
                        [
                            "criteriaType" => "critereSimple",
                            "criteriaId" => 2211, // Critères "ouvert aujourd'hui"
                            "values" => ["true"]
                        ]
                    ]
                ],
                'responseFields' => [
                    "nom",
                    "commune",
                    "moyensCommunications",
                    "identifiant",
                    "adresse",
                    "telephone",
                    "siteWeb",
                    "description",
                    "type"
                ],
                'count' => $limit,
                'first' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!isset($data['objetsTouristiques'])) {
                    $this->error('Format de réponse inattendu de l\'API Apidae');
                    $this->line('Réponse reçue : ' . $response->body());
                    return 1;
                }

                $results = $data['objetsTouristiques'];
                $this->info('Nombre d\'hébergements reçus : ' . count($results));

                return $this->processResults($results);
            } else {
                $this->error('Erreur lors de l\'appel à l\'API : ' . $response->status());
                $this->error('Réponse : ' . $response->body());
                $this->info('Utilisez --test pour tester avec des données de test');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Exception lors de l\'exécution : ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Handle test data for development purposes
     */
    private function handleTestData()
    {
        $this->info('Utilisation de données de test…');

        $testData = [
            [
                'identifiant' => 'APIDAE_001',
                'nom' => ['libelleFr' => 'Hôtel du Lac'],
                'commune' => ['nom' => 'Annecy'],
                'moyensCommunications' => [
                    ['type' => 'EMAIL', 'coordonnees' => 'contact@hoteldulac.fr']
                ],
                'status' => 'active'
            ],
            [
                'identifiant' => 'APIDAE_002',
                'nom' => ['libelleFr' => 'Gîte Les Alpages'],
                'commune' => ['nom' => 'Chamonix'],
                'moyensCommunications' => [
                    ['type' => 'EMAIL', 'coordonnees' => 'info@gite-alpages.com']
                ],
                'status' => 'pending'
            ],
            [
                'identifiant' => 'APIDAE_003',
                'nom' => ['libelleFr' => 'Chambre d\'hôte La Ferme'],
                'commune' => ['nom' => 'Megève'],
                'moyensCommunications' => [],
                'status' => 'inactive'
            ],
            [
                'identifiant' => 'APIDAE_004',
                'nom' => ['libelleFr' => 'Camping Les Pins'],
                'commune' => ['nom' => 'Thonon-les-Bains'],
                'moyensCommunications' => [
                    ['type' => 'EMAIL', 'coordonnees' => 'reservation@camping-pins.fr'],
                    ['type' => 'TELEPHONE', 'coordonnees' => '04.50.71.23.45']
                ],
                'status' => 'active'
            ],
            [
                'identifiant' => 'APIDAE_005',
                'nom' => ['libelleFr' => 'Résidence Les Alpages'],
                'commune' => ['nom' => 'Morzine'],
                'moyensCommunications' => [
                    ['type' => 'EMAIL', 'coordonnees' => 'contact@residence-alpages.com']
                ],
                'status' => 'pending'
            ]
        ];

        return $this->processResults($testData);
    }

    /**
     * Process the results and save to database
     */
    private function processResults($results)
    {
        $created = 0;
        $updated = 0;

        foreach ($results as $item) {
            // Extraction de l'email de manière plus sûre
            $email = null;
            if (isset($item['moyensCommunications']) && is_array($item['moyensCommunications'])) {
                $emailCommunication = collect($item['moyensCommunications'])
                    ->firstWhere('type', 'EMAIL');
                $email = $emailCommunication['coordonnees'] ?? null;
            }

            // Extraction du téléphone
            $phone = null;
            if (isset($item['moyensCommunications']) && is_array($item['moyensCommunications'])) {
                $phoneCommunication = collect($item['moyensCommunications'])
                    ->firstWhere('type', 'TELEPHONE');
                $phone = $phoneCommunication['coordonnees'] ?? null;
            }

            $accommodation = Accommodation::updateOrCreate(
                ['apidae_id' => $item['identifiant'] ?? $item['id'] ?? null],
                [
                    'name' => $item['nom']['libelleFr'] ?? $item['nom'] ?? 'Nom inconnu',
                    'city' => $item['commune']['nom'] ?? $item['commune'] ?? null,
                    'email' => $email,
                    'phone' => $phone,
                    'website' => $item['siteWeb'] ?? null,
                    'description' => $item['description']['libelleFr'] ?? $item['description'] ?? null,
                    'type' => $item['type']['libelleFr'] ?? $item['type'] ?? null,
                    'status' => $item['status'] ?? 'pending',
                ]
            );

            if ($accommodation->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->info("✅ Opération terminée avec succès !");
        $this->info("   - Hébergements créés : {$created}");
        $this->info("   - Hébergements mis à jour : {$updated}");
        $this->info("   - Total traité : " . ($created + $updated));

        return 0;
    }
}
