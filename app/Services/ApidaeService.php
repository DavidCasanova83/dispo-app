<?php

namespace App\Services;

use App\Models\Accommodation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApidaeService
{
    private string $apiKey;
    private string $projectId;
    private string $selectionId;
    private string $baseUrl = 'https://api.apidae-tourisme.com/api/v002';
    private AccommodationService $accommodationService;

    public function __construct(AccommodationService $accommodationService)
    {
        $this->apiKey = config('services.apidae.api_key', env('APIDAE_API_KEY'));
        $this->projectId = config('services.apidae.project_id', env('APIDAE_PROJECT_ID'));
        $this->selectionId = config('services.apidae.selection_id', env('APIDAE_SELECTION_ID'));
        $this->accommodationService = $accommodationService;
    }

    /**
     * Fetch accommodations from Apidae API
     */
    public function fetchAccommodations(int $limit = 150, bool $simple = false): array
    {
        $startTime = microtime(true);
        
        try {
            $this->validateConfiguration();

            $requestData = $this->buildRequestData($limit, $simple);
            
            Log::info('Apidae API request', [
                'limit' => $limit,
                'simple' => $simple,
                'project_id' => $this->projectId,
                'selection_id' => $this->selectionId
            ]);

            $response = Http::timeout(60)
                ->asForm()
                ->post($this->baseUrl . '/recherche/list-objets-touristiques', [
                    'query' => json_encode($requestData['query'])
                ]);

            if (!$response->successful()) {
                throw new \Exception("API Error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            
            if (!$this->validateApiResponse($data)) {
                throw new \Exception('Invalid API response format');
            }

            $duration = microtime(true) - $startTime;
            
            Log::info('Apidae API success', [
                'count' => count($data['objetsTouristiques']),
                'limit' => $limit,
                'duration_seconds' => round($duration, 3),
                'response_size' => strlen($response->body())
            ]);

            return $data['objetsTouristiques'];

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            Log::error('Apidae API error', [
                'message' => $e->getMessage(),
                'limit' => $limit,
                'duration_seconds' => round($duration, 3),
                'project_id' => $this->projectId,
                'selection_id' => $this->selectionId
            ]);
            
            throw $e;
        }
    }

    /**
     * Process and save accommodations data
     */
    public function processAndSaveAccommodations(array $apiData): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($apiData as $item) {
            try {
                $accommodationData = $this->processAccommodationData($item);
                
                if (!$accommodationData['apidae_id']) {
                    Log::warning('Skipping accommodation without apidae_id', ['item' => $item]);
                    $errors++;
                    continue;
                }

                $accommodation = Accommodation::updateOrCreate(
                    ['apidae_id' => $accommodationData['apidae_id']],
                    $accommodationData
                );

                if ($accommodation->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

            } catch (\Exception $e) {
                Log::error('Error processing accommodation', [
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }
        }

        // Clear cache after data update
        $this->accommodationService->clearCache();

        $result = [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'total_processed' => $created + $updated
        ];

        Log::info('Accommodation processing completed', $result);

        return $result;
    }

    /**
     * Process individual accommodation data from API
     */
    public function processAccommodationData(array $apiData): array
    {
        $email = null;
        $phone = null;
        $website = null;

        // Extract contact information
        if (isset($apiData['informations']['moyensCommunication'])) {
            foreach ($apiData['informations']['moyensCommunication'] as $communication) {
                $type = strtolower($communication['type']['libelleFr'] ?? '');
                $coordonnees = $communication['coordonnees']['fr'] ?? '';

                if (str_contains($type, 'mél') || str_contains($type, 'email')) {
                    $email = filter_var($coordonnees, FILTER_VALIDATE_EMAIL) ?: null;
                } elseif (str_contains($type, 'téléphone') || str_contains($type, 'phone')) {
                    $phone = $this->sanitizePhoneNumber($coordonnees);
                } elseif (str_contains($type, 'site web') || str_contains($type, 'url')) {
                    $website = filter_var($coordonnees, FILTER_VALIDATE_URL) ?: null;
                }
            }
        }

        // Extract type information
        $type = null;
        if (isset($apiData['informationsHebergementCollectif']['hebergementCollectifType']['libelleFr'])) {
            $type = $apiData['informationsHebergementCollectif']['hebergementCollectifType']['libelleFr'];
        } elseif (isset($apiData['informationsHotellerie']['hotellerieType']['libelleFr'])) {
            $type = $apiData['informationsHotellerie']['hotellerieType']['libelleFr'];
        }

        return [
            'apidae_id' => $apiData['id'] ?? $apiData['identifier'] ?? null,
            'name' => $this->extractName($apiData),
            'city' => $this->extractCity($apiData),
            'email' => $email,
            'phone' => $phone,
            'website' => $website,
            'description' => $this->extractDescription($apiData),
            'type' => $type,
            'status' => 'pending',
        ];
    }

    /**
     * Get test data for development
     */
    public function getTestData(): array
    {
        return [
            [
                'id' => 'APIDAE_TEST_001',
                'nom' => ['libelleFr' => 'Hôtel du Lac - Test'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Annecy']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Mél'], 'coordonnees' => ['fr' => 'contact@hoteldulac-test.fr']]
                    ]
                ],
                'presentation' => [
                    'descriptifCourt' => ['libelleFr' => 'Hôtel de test avec vue sur le lac']
                ]
            ],
            [
                'id' => 'APIDAE_TEST_002',
                'nom' => ['libelleFr' => 'Gîte Les Alpages - Test'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Chamonix']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Mél'], 'coordonnees' => ['fr' => 'info@gite-alpages-test.com']],
                        ['type' => ['libelleFr' => 'Téléphone'], 'coordonnees' => ['fr' => '04.50.71.23.45']]
                    ]
                ]
            ],
            [
                'id' => 'APIDAE_TEST_003',
                'nom' => ['libelleFr' => 'Camping Les Pins - Test'],
                'localisation' => [
                    'adresse' => [
                        'commune' => ['nom' => 'Thonon-les-Bains']
                    ]
                ],
                'informations' => [
                    'moyensCommunication' => [
                        ['type' => ['libelleFr' => 'Site web'], 'coordonnees' => ['fr' => 'https://camping-pins-test.fr']],
                        ['type' => ['libelleFr' => 'Téléphone'], 'coordonnees' => ['fr' => '+33 4 50 71 23 46']]
                    ]
                ]
            ]
        ];
    }

    /**
     * Validate API configuration
     */
    private function validateConfiguration(): void
    {
        if (!$this->apiKey || !$this->projectId || !$this->selectionId) {
            throw new \Exception('Missing Apidae API configuration. Check APIDAE_API_KEY, APIDAE_PROJECT_ID, and APIDAE_SELECTION_ID.');
        }
    }

    /**
     * Build request data for API call
     */
    private function buildRequestData(int $limit, bool $simple): array
    {
        $requestData = [
            'query' => [
                'selectionIds' => [$this->selectionId],
                'searchFields' => 'NOM_DESCRIPTION_CRITERES',
                'first' => 0,
                'count' => $limit,
                'order' => 'IDENTIFIANT',
                'asc' => true,
                'apiKey' => $this->apiKey,
                'projetId' => $this->projectId
            ]
        ];

        if (!$simple) {
            // Add additional criteria if needed
            // $requestData['query']['criteresQuery'] = json_encode([...]);
        }

        return $requestData;
    }

    /**
     * Validate API response format
     */
    private function validateApiResponse(array $data): bool
    {
        return isset($data['objetsTouristiques']) && 
               is_array($data['objetsTouristiques']);
    }

    /**
     * Extract name from API data
     */
    private function extractName(array $apiData): string
    {
        return $apiData['nom']['libelleFr'] ?? 
               $apiData['nom'] ?? 
               'Nom inconnu';
    }

    /**
     * Extract city from API data
     */
    private function extractCity(array $apiData): ?string
    {
        return $apiData['localisation']['adresse']['commune']['nom'] ?? null;
    }

    /**
     * Extract description from API data
     */
    private function extractDescription(array $apiData): ?string
    {
        return $apiData['presentation']['descriptifCourt']['libelleFr'] ?? 
               $apiData['presentation']['descriptifDetaille']['libelleFr'] ?? 
               null;
    }

    /**
     * Sanitize phone number
     */
    private function sanitizePhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Validate minimum length
        if (strlen($cleaned) < 10) {
            return null;
        }

        // Convert French format to international if needed
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '+33' . substr($cleaned, 1);
        }

        return $cleaned;
    }

    /**
     * Get API status and configuration info
     */
    public function getApiStatus(): array
    {
        return [
            'configured' => !empty($this->apiKey) && !empty($this->projectId) && !empty($this->selectionId),
            'api_key_set' => !empty($this->apiKey),
            'project_id_set' => !empty($this->projectId),
            'selection_id_set' => !empty($this->selectionId),
            'base_url' => $this->baseUrl,
            'project_id' => $this->projectId,
            'selection_id' => $this->selectionId
        ];
    }
}