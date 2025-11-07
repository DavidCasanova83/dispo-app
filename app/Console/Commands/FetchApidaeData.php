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
            $simple = $this->option('simple');

            $this->info("Configuration utilisée :");
            $this->line("  - Project ID: " . env('APIDAE_PROJECT_ID'));
            $this->line("  - Selection ID: " . env('APIDAE_SELECTION_ID'));
            $this->line("  - Limite: {$limit} hébergements");
            $this->line("  - Mode simple: " . ($simple ? 'Oui' : 'Non'));
            $this->line("");

            // Préparer les paramètres de la requête
            $requestData = [
                'query' => [
                    'selectionIds' => [env('APIDAE_SELECTION_ID')],
                    'searchFields' => 'NOM_DESCRIPTION_CRITERES',
                    'first' => 0,
                    'count' => $limit,
                    'order' => 'IDENTIFIANT',
                    'asc' => true,
                    'apiKey' => env('APIDAE_API_KEY'),
                    'projetId' => env('APIDAE_PROJECT_ID')
                ]
            ];

            // // Ajouter les critères seulement si pas en mode simple
            // if (!$simple) {
            //     $requestData['query']['criteresQuery'] = json_encode([
            //         [
            //             "id" => 2211,
            //             "type" => "Selection",
            //             "valeurs" => ["true"]
            //         ]
            //     ]);
            // }


            $url = 'https://api.apidae-tourisme.com/api/v002/recherche/list-objets-touristiques';

            $this->line("Payload envoyé :");
            $this->line(json_encode($requestData['query'], JSON_PRETTY_PRINT));

            $response = Http::timeout(60)
                ->asForm()
                ->post($url, [
                    'query' => json_encode($requestData['query'])
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
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Annecy']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Mél'], 'coordonnees' => ['fr' => 'casanova.83130@gmail.com']],
                        ['type' => ['libelleFr' => 'Téléphone'], 'coordonnees' => ['fr' => '04.50.45.12.34']]
                    ]
                ],
                'status' => 'active'
            ],
            [
                'identifiant' => 'APIDAE_002',
                'nom' => ['libelleFr' => 'Gîte Les Alpages'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Chamonix']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Mél'], 'coordonnees' => ['fr' => 'casanova.83130@gmail.com']]
                    ]
                ],
                'status' => 'pending'
            ],
            [
                'identifiant' => 'APIDAE_003',
                'nom' => ['libelleFr' => 'Chambre d\'hôte La Ferme'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Megève']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => []
                ],
                'status' => 'inactive'
            ],
            [
                'identifiant' => 'APIDAE_004',
                'nom' => ['libelleFr' => 'Camping Les Pins'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Thonon-les-Bains']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Mél'], 'coordonnees' => ['fr' => 'casanova.83130@gmail.com']],
                        ['type' => ['libelleFr' => 'Téléphone'], 'coordonnees' => ['fr' => '04.50.71.23.45']],
                        ['type' => ['libelleFr' => 'Site web'], 'coordonnees' => ['fr' => 'https://camping-pins.fr']]
                    ]
                ],
                'status' => 'active'
            ],
            [
                'identifiant' => 'APIDAE_005',
                'nom' => ['libelleFr' => 'Résidence Les Alpages'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Morzine']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Email'], 'coordonnees' => ['fr' => 'casanova.83130@gmail.com']],
                        ['type' => ['libelleFr' => 'Téléphone'], 'coordonnees' => ['fr' => '04.50.79.88.77']]
                    ]
                ],
                'presentation' => [
                    'descriptifCourt' => ['libelleFr' => 'Magnifique résidence au cœur des Alpes']
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
            $phone = null;
            $website = null;

            if (isset($item['informations']['moyensCommunication']) && is_array($item['informations']['moyensCommunication'])) {
                foreach ($item['informations']['moyensCommunication'] as $communication) {
                    $type = $communication['type']['libelleFr'] ?? '';
                    $coordonnees = $communication['coordonnees']['fr'] ?? '';

                    if (str_contains(strtolower($type), 'mél') || str_contains(strtolower($type), 'email')) {
                        $email = $coordonnees;
                    } elseif (str_contains(strtolower($type), 'téléphone') || str_contains(strtolower($type), 'phone')) {
                        $phone = $coordonnees;
                    } elseif (str_contains(strtolower($type), 'site web') || str_contains(strtolower($type), 'url')) {
                        $website = $coordonnees;
                    }
                }
            }

            $accommodation = Accommodation::updateOrCreate(
                ['apidae_id' => $item['id'] ?? $item['identifiant'] ?? $item['id'] ?? null],
                [
                    'name' => $item['nom']['libelleFr'] ?? $item['nom'] ?? 'Nom inconnu',
                    'city' => $item['localisation']['adresse']['commune']['nom'] ?? null,
                    'email' => $email,
                    'phone' => $phone,
                    'website' => $website,
                    'description' => $item['presentation']['descriptifCourt']['libelleFr'] ?? null,
                    'type' => $item['type'] ?? null,
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
