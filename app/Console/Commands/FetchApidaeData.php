<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Accommodation;
use Carbon\Carbon;

class FetchApidaeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidae:fetch {--test : Utiliser des données de test au lieu de l\'API} {--all : Récupérer tous les hébergements disponibles} {--limit=150 : Limite du nombre d\'hébergements à récupérer} {--simple : Utiliser une requête simple sans critères}';

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
        // Log de démarrage
        Log::info('Démarrage de la récupération Apidae', [
            'mode_test' => $this->option('test'),
            'mode_all' => $this->option('all'),
            'limit' => $this->option('limit'),
            'mode_simple' => $this->option('simple'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        if ($this->option('test')) {
            return $this->handleTestData();
        }

        $this->info('Récupération des hébergements depuis Apidae…');

        // Log du statut des variables d'environnement
        Log::info('Statut des variables d\'environnement Apidae', [
            'APIDAE_API_KEY' => env('APIDAE_API_KEY') ? 'défini' : 'MANQUANT',
            'APIDAE_PROJECT_ID' => env('APIDAE_PROJECT_ID') ? 'défini' : 'MANQUANT',
            'APIDAE_SELECTION_ID' => env('APIDAE_SELECTION_ID') ? 'défini' : 'MANQUANT',
        ]);

        try {
            $all = $this->option('all');
            $limit = $this->option('limit');
            $simple = $this->option('simple');

            // Si --all est activé, on ignore --limit
            if ($all) {
                $this->info("Mode: Récupération de TOUS les hébergements disponibles");
            } else {
                $this->info("Mode: Récupération limitée à {$limit} hébergements");
            }

            $this->info("Configuration utilisée :");
            $this->line("  - Project ID: " . env('APIDAE_PROJECT_ID'));
            $this->line("  - Selection ID: " . env('APIDAE_SELECTION_ID'));
            $this->line("  - Mode simple: " . ($simple ? 'Oui' : 'Non'));
            $this->line("");

            Log::info('Configuration Apidae', [
                'project_id' => env('APIDAE_PROJECT_ID'),
                'selection_id' => env('APIDAE_SELECTION_ID'),
                'mode_simple' => $simple,
                'target_count' => $all ? 'tous' : $limit,
            ]);

            $url = 'https://api.apidae-tourisme.com/api/v002/recherche/list-objets-touristiques';
            $pageSize = 20; // Limite de l'API Apidae par requête
            $allResults = [];
            $totalAvailable = null;

            // Première requête pour connaître le nombre total d'hébergements
            $this->info('Récupération du nombre total d\'hébergements...');

            $firstRequestData = [
                'selectionIds' => [env('APIDAE_SELECTION_ID')],
                'searchFields' => 'NOM_DESCRIPTION_CRITERES',
                'first' => 0,
                'count' => $pageSize,
                'order' => 'IDENTIFIANT',
                'asc' => true,
                'apiKey' => env('APIDAE_API_KEY'),
                'projetId' => env('APIDAE_PROJECT_ID')
            ];

            $firstResponse = Http::timeout(60)
                ->asForm()
                ->post($url, [
                    'query' => json_encode($firstRequestData)
                ]);

            if (!$firstResponse->successful()) {
                Log::error('Erreur API Apidae - première requête', [
                    'status_http' => $firstResponse->status(),
                    'response_body' => $firstResponse->body(),
                    'url' => $url,
                    'request_data' => $firstRequestData,
                ]);
                $this->error('Erreur lors de l\'appel à l\'API : ' . $firstResponse->status());
                $this->error('Réponse : ' . $firstResponse->body());
                $this->info('Utilisez --test pour tester avec des données de test');
                return 1;
            }

            $firstData = $firstResponse->json();

            if (!isset($firstData['numFound']) || !isset($firstData['objetsTouristiques'])) {
                Log::error('Format de réponse Apidae inattendu', [
                    'response_body' => $firstResponse->body(),
                    'keys_recues' => array_keys($firstData ?? []),
                ]);
                $this->error('Format de réponse inattendu de l\'API Apidae');
                $this->line('Réponse reçue : ' . $firstResponse->body());
                return 1;
            }

            $totalAvailable = $firstData['numFound'];
            $allResults = array_merge($allResults, $firstData['objetsTouristiques']);

            Log::info('Première requête API Apidae réussie', [
                'total_disponible' => $totalAvailable,
                'hebergements_recus' => count($firstData['objetsTouristiques']),
                'status_http' => $firstResponse->status(),
            ]);

            $this->info("✓ {$totalAvailable} hébergements disponibles au total");

            // Déterminer combien d'hébergements on doit récupérer
            $targetCount = $all ? $totalAvailable : min($limit, $totalAvailable);
            $pagesNeeded = ceil($targetCount / $pageSize);

            $this->info("Récupération de {$targetCount} hébergements en {$pagesNeeded} page(s)...");
            $this->line("");

            // Boucle de pagination pour récupérer les pages suivantes
            for ($page = 1; $page < $pagesNeeded; $page++) {
                $first = $page * $pageSize;

                // Ne pas dépasser le nombre cible
                if ($first >= $targetCount) {
                    break;
                }

                $this->line("→ Page " . ($page + 1) . "/{$pagesNeeded} (hébergements " . ($first + 1) . "-" . min($first + $pageSize, $targetCount) . "/{$targetCount})");

                $requestData = [
                    'selectionIds' => [env('APIDAE_SELECTION_ID')],
                    'searchFields' => 'NOM_DESCRIPTION_CRITERES',
                    'first' => $first,
                    'count' => $pageSize,
                    'order' => 'IDENTIFIANT',
                    'asc' => true,
                    'apiKey' => env('APIDAE_API_KEY'),
                    'projetId' => env('APIDAE_PROJECT_ID')
                ];

                $response = Http::timeout(60)
                    ->asForm()
                    ->post($url, [
                        'query' => json_encode($requestData)
                    ]);

                if (!$response->successful()) {
                    Log::error('Erreur API Apidae - pagination', [
                        'page' => $page + 1,
                        'status_http' => $response->status(),
                        'response_body' => $response->body(),
                        'hebergements_deja_recuperes' => count($allResults),
                    ]);
                    $this->error('Erreur lors de l\'appel à l\'API (page ' . ($page + 1) . ') : ' . $response->status());
                    $this->warn('Arrêt de la récupération. Traitement des ' . count($allResults) . ' hébergements déjà récupérés...');
                    break;
                }

                $data = $response->json();

                if (isset($data['objetsTouristiques']) && is_array($data['objetsTouristiques'])) {
                    $allResults = array_merge($allResults, $data['objetsTouristiques']);
                }

                // Petite pause pour ne pas surcharger l'API
                usleep(100000); // 100ms
            }

            $this->line("");
            $this->info('✓ ' . count($allResults) . ' hébergements récupérés au total');
            $this->line("");

            Log::info('Récupération Apidae terminée', [
                'total_hebergements' => count($allResults),
                'pages_traitees' => $pagesNeeded,
            ]);

            // Supprimer les absents uniquement en mode --all (synchronisation complète)
            return $this->processResults($allResults, $all);

        } catch (\Exception $e) {
            Log::error('Exception lors de la récupération Apidae', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
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
    private function processResults($results, bool $deleteAbsent = false)
    {
        Log::info('Début du traitement des résultats Apidae', [
            'nombre_resultats' => count($results),
            'mode_suppression' => $deleteAbsent,
        ]);

        $created = 0;
        $updated = 0;
        $deleted = 0;

        // Collecter les apidae_id pour la suppression
        $apidaeIds = [];

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

            $apidaeId = $item['id'] ?? $item['identifiant'] ?? null;

            if (!$apidaeId) {
                Log::warning('Hébergement sans identifiant Apidae', [
                    'donnees_brutes' => json_encode($item),
                ]);
            }

            $apidaeIds[] = $apidaeId;

            $accommodation = Accommodation::updateOrCreate(
                ['apidae_id' => $apidaeId],
                [
                    'name' => $item['nom']['libelleFr'] ?? $item['nom'] ?? 'Nom inconnu',
                    'city' => $item['localisation']['adresse']['commune']['nom'] ?? null,
                    'email' => $email,
                    'phone' => $phone,
                    'website' => $website,
                    'description' => $item['presentation']['descriptifCourt']['libelleFr'] ?? null,
                    'type' => $item['type'] ?? null,
                    'status' => $item['status'] ?? 'en_attente',
                ]
            );

            if ($accommodation->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        // Supprimer les hébergements absents de la sélection (uniquement en mode --all)
        if ($deleteAbsent && !empty($apidaeIds)) {
            $deleted = Accommodation::whereNotIn('apidae_id', array_filter($apidaeIds))->delete();

            if ($deleted > 0) {
                Log::warning('Suppression des hébergements absents', [
                    'ids_conserves' => count(array_filter($apidaeIds)),
                    'hebergements_supprimes' => $deleted,
                ]);
            }
        }

        $this->info("✅ Opération terminée avec succès !");
        $this->info("   - Hébergements créés : {$created}");
        $this->info("   - Hébergements mis à jour : {$updated}");
        if ($deleteAbsent) {
            $this->info("   - Hébergements supprimés : {$deleted}");
        }
        $this->info("   - Total traité : " . ($created + $updated));

        Log::info('Récupération automatique des hébergements terminée', [
            'hebergements_crees' => $created,
            'hebergements_mis_a_jour' => $updated,
            'hebergements_supprimes' => $deleted,
            'total_traite' => $created + $updated,
        ]);

        return 0;
    }
}
