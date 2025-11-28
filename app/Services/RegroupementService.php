<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RegroupementService
{
    protected array $mappings = [];

    public function __construct()
    {
        $this->loadMappings();
    }

    /**
     * Load the commune to regroupement mappings from CSV.
     */
    protected function loadMappings(): void
    {
        $this->mappings = Cache::remember('regroupement_mappings', 3600, function () {
            $mappings = [];
            $csvPath = database_path('data/regroupement.csv');

            if (!file_exists($csvPath)) {
                return $mappings;
            }

            $handle = fopen($csvPath, 'r');
            if ($handle === false) {
                return $mappings;
            }

            // Skip header row
            fgetcsv($handle, 0, ';');

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) >= 3) {
                    $commune = trim($row[1]);
                    $regroupement = trim($row[2]);

                    // Store with lowercase key for case-insensitive lookup
                    $mappings[mb_strtolower($commune)] = $regroupement;
                }
            }

            fclose($handle);

            return $mappings;
        });
    }

    /**
     * Get the regroupement for a given city/commune.
     */
    public function getRegroupement(?string $city): ?string
    {
        if (empty($city)) {
            return null;
        }

        return $this->mappings[mb_strtolower(trim($city))] ?? null;
    }

    /**
     * Clear the cached mappings.
     */
    public function clearCache(): void
    {
        Cache::forget('regroupement_mappings');
        $this->loadMappings();
    }
}
