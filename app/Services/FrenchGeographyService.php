<?php

namespace App\Services;

class FrenchGeographyService
{
    /**
     * Codes prioritaires affichés en tête de liste (dans cet ordre).
     */
    protected const PRIORITY_DEPARTMENT_CODES = ['04', '06', '83', '13'];

    /**
     * Get all departments from configuration, sorted with priority codes first
     * then by numeric department code (handles Corsica 2A/2B and DOM-TOM).
     *
     * @return array
     */
    public function getAllDepartments(): array
    {
        $departments = config('french_geography.departments', []);
        return $this->sortDepartments($departments);
    }

    /**
     * Sort departments: priority list first (in their declared order),
     * then the rest by numeric code.
     *
     * @param array $departments
     * @return array
     */
    protected function sortDepartments(array $departments): array
    {
        $priority = self::PRIORITY_DEPARTMENT_CODES;

        usort($departments, function ($a, $b) use ($priority) {
            $aPriority = array_search($a['code'], $priority, true);
            $bPriority = array_search($b['code'], $priority, true);

            if ($aPriority !== false && $bPriority !== false) {
                return $aPriority <=> $bPriority;
            }
            if ($aPriority !== false) {
                return -1;
            }
            if ($bPriority !== false) {
                return 1;
            }

            return $this->departmentCodeRank($a['code']) <=> $this->departmentCodeRank($b['code']);
        });

        return array_values($departments);
    }

    /**
     * Numeric rank for a department code, used for natural sorting.
     * Handles Corsica (2A/2B) and DOM-TOM (971+).
     *
     * @param string $code
     * @return float
     */
    protected function departmentCodeRank(string $code): float
    {
        if ($code === '2A') {
            return 20.1;
        }
        if ($code === '2B') {
            return 20.2;
        }
        if (is_numeric($code)) {
            return (float) (int) $code;
        }
        return 9999;
    }

    /**
     * Get all regions from configuration.
     *
     * @return array
     */
    public function getAllRegions(): array
    {
        return config('french_geography.regions', []);
    }

    /**
     * Search departments by query (code or name).
     * Performs case-insensitive search with accent normalization.
     *
     * @param string $query
     * @param int|null $limit
     * @return array
     */
    public function searchDepartments(string $query, ?int $limit = null): array
    {
        if (empty(trim($query))) {
            return $this->getAllDepartments();
        }

        $normalizedQuery = $this->normalizeString($query);
        $departments = $this->getAllDepartments();

        // Filter departments matching query in code or name
        $results = array_filter($departments, function ($department) use ($normalizedQuery) {
            $normalizedCode = $this->normalizeString($department['code']);
            $normalizedName = $this->normalizeString($department['name']);
            $normalizedRegion = $this->normalizeString($department['region']);

            return str_contains($normalizedCode, $normalizedQuery)
                || str_contains($normalizedName, $normalizedQuery)
                || str_contains($normalizedRegion, $normalizedQuery);
        });

        // Sort by relevance: exact code match > name starts with > name contains
        usort($results, function ($a, $b) use ($normalizedQuery) {
            $aCode = $this->normalizeString($a['code']);
            $bCode = $this->normalizeString($b['code']);
            $aName = $this->normalizeString($a['name']);
            $bName = $this->normalizeString($b['name']);

            // Exact code match
            if ($aCode === $normalizedQuery) {
                return -1;
            }
            if ($bCode === $normalizedQuery) {
                return 1;
            }

            // Name starts with query
            $aStartsWith = str_starts_with($aName, $normalizedQuery);
            $bStartsWith = str_starts_with($bName, $normalizedQuery);

            if ($aStartsWith && !$bStartsWith) {
                return -1;
            }
            if (!$aStartsWith && $bStartsWith) {
                return 1;
            }

            // Alphabetical by name
            return strcmp($a['name'], $b['name']);
        });

        if ($limit !== null) {
            return array_slice($results, 0, $limit);
        }

        return array_values($results);
    }

    /**
     * Get departments by region name.
     *
     * @param string $regionName
     * @return array
     */
    public function getDepartmentsByRegion(string $regionName): array
    {
        $departments = $this->getAllDepartments();

        return array_values(array_filter($departments, function ($department) use ($regionName) {
            return $department['region'] === $regionName;
        }));
    }

    /**
     * Get departments grouped by region.
     *
     * @return array
     */
    public function getDepartmentsGroupedByRegion(): array
    {
        $departments = $this->getAllDepartments();
        $grouped = [];

        foreach ($departments as $department) {
            $region = $department['region'];
            if (!isset($grouped[$region])) {
                $grouped[$region] = [];
            }
            $grouped[$region][] = $department;
        }

        return $grouped;
    }

    /**
     * Validate if a department code exists.
     *
     * @param string|null $code
     * @return bool
     */
    public function isValidDepartmentCode(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        $departments = $this->getAllDepartments();

        foreach ($departments as $department) {
            if (strtoupper($department['code']) === strtoupper(trim($code))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if a department name exists.
     *
     * @param string|null $name
     * @return bool
     */
    public function isValidDepartmentName(?string $name): bool
    {
        if (empty($name)) {
            return false;
        }

        $normalizedName = $this->normalizeString($name);
        $departments = $this->getAllDepartments();

        foreach ($departments as $department) {
            if ($this->normalizeString($department['name']) === $normalizedName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if a department (code or name format) exists.
     *
     * @param string|null $department
     * @return bool
     */
    public function isValidDepartment(?string $department): bool
    {
        if (empty($department)) {
            return false;
        }

        // Try to parse "04 - Alpes-de-Haute-Provence" format
        if (str_contains($department, ' - ')) {
            $parts = explode(' - ', $department, 2);
            $code = trim($parts[0]);
            $name = trim($parts[1] ?? '');

            return $this->isValidDepartmentCode($code) && $this->isValidDepartmentName($name);
        }

        // Check if it's just a code or just a name
        return $this->isValidDepartmentCode($department) || $this->isValidDepartmentName($department);
    }

    /**
     * Get department by code.
     *
     * @param string $code
     * @return array|null
     */
    public function getDepartmentByCode(string $code): ?array
    {
        $departments = $this->getAllDepartments();

        foreach ($departments as $department) {
            if (strtoupper($department['code']) === strtoupper(trim($code))) {
                return $department;
            }
        }

        return null;
    }

    /**
     * Get department by name.
     *
     * @param string $name
     * @return array|null
     */
    public function getDepartmentByName(string $name): ?array
    {
        $normalizedName = $this->normalizeString($name);
        $departments = $this->getAllDepartments();

        foreach ($departments as $department) {
            if ($this->normalizeString($department['name']) === $normalizedName) {
                return $department;
            }
        }

        return null;
    }

    /**
     * Format department for display (code - name).
     *
     * @param array $department
     * @return string
     */
    public function formatDepartment(array $department): string
    {
        return sprintf('%s - %s', $department['code'], $department['name']);
    }

    /**
     * Parse department string (code - name) to extract code.
     *
     * @param string $department
     * @return string|null
     */
    public function extractCodeFromFormatted(string $department): ?string
    {
        if (str_contains($department, ' - ')) {
            $parts = explode(' - ', $department, 2);
            return trim($parts[0]);
        }

        return trim($department);
    }

    /**
     * Normalize string for search (lowercase, remove accents).
     *
     * @param string $string
     * @return string
     */
    protected function normalizeString(string $string): string
    {
        $string = mb_strtolower($string, 'UTF-8');

        // Remove accents
        $string = str_replace(
            ['à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'd', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'],
            $string
        );

        return $string;
    }

    /**
     * Get formatted departments list (code - name format).
     *
     * @return array
     */
    public function getFormattedDepartmentsList(): array
    {
        $departments = $this->getAllDepartments();

        return array_map(function ($department) {
            return $this->formatDepartment($department);
        }, $departments);
    }

    /*
    |--------------------------------------------------------------------------
    | Country Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get all countries from configuration.
     *
     * @return array
     */
    public function getAllCountries(): array
    {
        return config('french_geography.countries', []);
    }

    /**
     * Search countries by query (case-insensitive with accent normalization).
     *
     * @param string $query
     * @param int|null $limit
     * @return array
     */
    public function searchCountries(string $query, ?int $limit = null): array
    {
        if (empty(trim($query))) {
            return $this->getAllCountries();
        }

        $normalizedQuery = $this->normalizeString($query);
        $countries = $this->getAllCountries();

        // Filter countries matching query
        $results = array_filter($countries, function ($country) use ($normalizedQuery) {
            $normalizedCountry = $this->normalizeString($country);
            return str_contains($normalizedCountry, $normalizedQuery);
        });

        // Sort by relevance: exact match > starts with > contains
        usort($results, function ($a, $b) use ($normalizedQuery) {
            $aNorm = $this->normalizeString($a);
            $bNorm = $this->normalizeString($b);

            // Exact match
            if ($aNorm === $normalizedQuery) {
                return -1;
            }
            if ($bNorm === $normalizedQuery) {
                return 1;
            }

            // Starts with query
            $aStartsWith = str_starts_with($aNorm, $normalizedQuery);
            $bStartsWith = str_starts_with($bNorm, $normalizedQuery);

            if ($aStartsWith && !$bStartsWith) {
                return -1;
            }
            if (!$aStartsWith && $bStartsWith) {
                return 1;
            }

            // Alphabetical
            return strcmp($a, $b);
        });

        if ($limit !== null) {
            return array_slice($results, 0, $limit);
        }

        return array_values($results);
    }

    /**
     * Validate if a country name exists.
     *
     * @param string|null $country
     * @return bool
     */
    public function isValidCountry(?string $country): bool
    {
        if (empty($country)) {
            return false;
        }

        $normalizedCountry = $this->normalizeString($country);
        $countries = $this->getAllCountries();

        foreach ($countries as $countryName) {
            if ($this->normalizeString($countryName) === $normalizedCountry) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get country by exact name match (case-insensitive with accent normalization).
     *
     * @param string $name
     * @return string|null
     */
    public function getCountryByName(string $name): ?string
    {
        $normalizedName = $this->normalizeString($name);
        $countries = $this->getAllCountries();

        foreach ($countries as $country) {
            if ($this->normalizeString($country) === $normalizedName) {
                return $country;
            }
        }

        return null;
    }
}
