<?php

namespace App\Services;

use App\Models\Qualification;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class QualificationStatisticsV3Service
{
    /**
     * Départements de la région PACA (codes INSEE).
     */
    public const PACA_DEPARTMENTS = ['04', '05', '06', '13', '83', '84'];

    /**
     * Code du département Alpes-de-Haute-Provence.
     */
    public const AHP_DEPARTMENT = '04';

    /**
     * Pays considérés comme européens (hors France), pour la répartition
     * France / Europe / Reste du monde.
     */
    public const EUROPEAN_COUNTRIES = [
        'Albanie', 'Allemagne', 'Andorre', 'Angleterre', 'Autriche', 'Belgique', 'Biélorussie',
        'Bosnie-Herzégovine', 'Bulgarie', 'Chypre', 'Croatie', 'Danemark', 'Écosse', 'Espagne',
        'Estonie', 'Finlande', 'Grèce', 'Hongrie', 'Irlande', 'Irlande du Nord', 'Islande',
        'Italie', 'Kosovo', 'Lettonie', 'Liechtenstein', 'Lituanie', 'Luxembourg',
        'Macédoine du Nord', 'Malte', 'Moldavie', 'Monaco', 'Monténégro', 'Norvège',
        'Pays de Galles', 'Pays-Bas', 'Pologne', 'Portugal', 'République tchèque', 'Roumanie',
        'Royaume-Uni', 'Russie', 'Saint-Marin', 'Serbie', 'Slovaquie', 'Slovénie', 'Suède',
        'Suisse', 'Ukraine', 'Vatican',
    ];

    /**
     * Types de visiteur exposés dans la répartition.
     */
    public const VISITOR_TYPES = ['Habitant', 'Socio Pro', 'Touriste'];

    /**
     * Tranches d'âge proposées comme filtre. « 0-18 » n'existe plus dans le
     * formulaire mais reste présent sur les anciennes qualifications.
     */
    public const AGE_GROUP_OPTIONS = ['0-6', '6-12', '12-18', '0-18', '18-25', '25-40', '40-60', '60+', 'Inconnu'];

    protected ?Collection $cachedQualifications = null;
    protected ?string $cacheKey = null;

    /** @var array<int, string> Tranches d'âge retenues ; vide = pas de filtre. */
    protected array $ageGroupFilter = [];

    /**
     * Restreint toutes les statistiques aux qualifications comportant au moins
     * une des tranches d'âge données. Un tableau vide désactive le filtre.
     */
    public function setAgeGroupFilter(array $ageGroups): static
    {
        $ageGroups = array_values(array_intersect(self::AGE_GROUP_OPTIONS, $ageGroups));

        if ($ageGroups !== $this->ageGroupFilter) {
            $this->cachedQualifications = null;
            $this->cacheKey = null;
        }

        $this->ageGroupFilter = $ageGroups;

        return $this;
    }

    /**
     * Load and cache qualifications for the current request.
     */
    public function getQualifications(?string $city, ?string $startDate, ?string $endDate): Collection
    {
        $key = md5(($city ?? 'all') . ($startDate ?? '') . ($endDate ?? '') . '|' . implode(',', $this->ageGroupFilter));

        if ($this->cacheKey === $key && $this->cachedQualifications !== null) {
            return $this->cachedQualifications;
        }

        $query = $this->baseQuery($city, $startDate, $endDate);
        $qualifications = $query->select(['id', 'city', 'form_data', 'user_id', 'created_at'])->get();

        if (!empty($this->ageGroupFilter)) {
            $wanted = array_flip($this->ageGroupFilter);
            $qualifications = $qualifications->filter(function ($qualification) use ($wanted) {
                foreach ((array) ($qualification->form_data['ageGroups'] ?? []) as $ageGroup) {
                    if (isset($wanted[$ageGroup])) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $this->cachedQualifications = $qualifications;
        $this->cacheKey = $key;

        return $this->cachedQualifications;
    }

    /**
     * Extrait la liste des pays d'une qualification, en supportant
     * l'ancien format (string `country`) et le nouveau (array `countries`).
     *
     * @return array<int, string>
     */
    protected function extractCountries(array $formData): array
    {
        if (isset($formData['countries']) && is_array($formData['countries']) && !empty($formData['countries'])) {
            return array_values($formData['countries']);
        }

        $legacy = $formData['country'] ?? null;
        if (!$legacy) {
            return [];
        }

        // Ancien format avec "Autre" + otherCountry
        if ($legacy === 'Autre' && !empty($formData['otherCountry'])) {
            return [$formData['otherCountry']];
        }

        // Une qualification migrée peut contenir plusieurs pays joints par virgule
        if (str_contains($legacy, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $legacy))));
        }

        return [$legacy];
    }

    /**
     * Vrai si la qualification ne contient que la France (pas d'international).
     */
    protected function isFranceOnly(array $formData): bool
    {
        $countries = $this->extractCountries($formData);
        // On ignore le marqueur "Inconnu" (origine non renseignée), qui ne doit
        // pas être compté comme international.
        $countries = array_values(array_filter($countries, fn($c) => $c !== 'Inconnu'));
        if (empty($countries)) {
            return true; // par défaut historique (origine inconnue = non international)
        }
        return count(array_filter($countries, fn($c) => $c !== 'France')) === 0;
    }

    /**
     * Base query: completed qualifications, filtered by city and date range.
     */
    protected function baseQuery(?string $city, ?string $startDate, ?string $endDate)
    {
        $query = Qualification::query()->where('completed', true);

        if ($city && $city !== 'all') {
            $query->where('city', $city);
        }

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }

    /**
     * Get list of active cities (all 5 or single selected).
     */
    public function getActiveCities(?string $city): array
    {
        if ($city && $city !== 'all') {
            return [$city];
        }

        return array_keys(Qualification::getCities());
    }

    /**
     * Normalize data: each city weighted equally.
     *
     * Input: ['annot' => ['Rando' => 40, 'Héberg' => 60], ...]
     * $qualificationCountsByCity: ['annot' => 58, 'entrevaux' => 99, ...]
     *   = the number of qualifications (forms) per city, used as denominator.
     *   For multi-value fields (generalRequests, ageGroups), the sum of occurrences
     *   exceeds the number of forms, so we must divide by form count, not occurrence count.
     *   If null, falls back to array_sum (correct only for single-value fields like profile).
     *
     * Output: ['normalized' => ['Rando' => 45.2, ...], 'perCity' => [...], 'cityTotals' => [...]]
     */
    public function normalize(array $dataByCities, ?array $qualificationCountsByCity = null): array
    {
        $cityTotals = [];
        $cityPercentages = [];

        // Step 1: compute per-city percentages
        foreach ($dataByCities as $cityKey => $items) {
            // Use qualification count as denominator if provided, otherwise sum of occurrences
            $total = $qualificationCountsByCity[$cityKey] ?? array_sum($items);
            $cityTotals[$cityKey] = $total;

            if ($total === 0) {
                continue; // exclude cities with 0 qualifications
            }

            $cityPercentages[$cityKey] = [];
            foreach ($items as $label => $count) {
                $cityPercentages[$cityKey][$label] = round(($count / $total) * 100, 2);
            }
        }

        // Step 2: average percentages across cities
        $allLabels = [];
        foreach ($cityPercentages as $items) {
            foreach (array_keys($items) as $label) {
                $allLabels[$label] = true;
            }
        }

        $cityCount = count($cityPercentages);
        $normalized = [];

        if ($cityCount > 0) {
            foreach (array_keys($allLabels) as $label) {
                $sum = 0;
                foreach ($cityPercentages as $items) {
                    $sum += $items[$label] ?? 0;
                }
                $normalized[$label] = round($sum / $cityCount, 1);
            }
        }

        // Sort descending
        arsort($normalized);

        return [
            'normalized' => $normalized,
            'perCity' => $dataByCities,
            'perCityPct' => $cityPercentages,
            'cityTotals' => $cityTotals,
        ];
    }

    /**
     * Get reliability level for a given count.
     */
    public function getReliability(int $count): string
    {
        if ($count >= 200) return 'high';
        if ($count >= 100) return 'good';
        if ($count >= 50) return 'medium';
        if ($count >= 20) return 'low';
        return 'very_low';
    }

    /**
     * Get reliability labels in French.
     */
    public static function reliabilityLabel(string $level): string
    {
        return match ($level) {
            'high' => 'Fiabilité élevée',
            'good' => 'Fiabilité bonne',
            'medium' => 'Fiabilité moyenne',
            'low' => 'Fiabilité faible',
            'very_low' => 'Fiabilité très faible',
            default => '',
        };
    }

    // ──────────── KPIs ────────────

    public function getKPIs(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $total = $qualifications->count();

        // Average per day
        $avgPerDay = 0;
        if ($total > 0 && $startDate && $endDate) {
            $days = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
            $avgPerDay = round($total / $days, 1);
        } elseif ($total > 0) {
            $firstDate = $qualifications->min('created_at');
            $days = max(1, Carbon::parse($firstDate)->diffInDays(now()) + 1);
            $avgPerDay = round($total / $days, 1);
        }

        // % international
        $internationalPct = $this->computeInternationalPct($qualifications, $city, $mode);

        // Dominant profile
        $profiles = $qualifications->pluck('form_data.profile')->filter()->countBy();
        $dominantProfile = $profiles->isNotEmpty() ? $profiles->sortDesc()->keys()->first() : 'Non renseigné';

        // Dominant age range
        $ageGroups = $qualifications->flatMap(fn($q) => $q->form_data['ageGroups'] ?? [])->countBy();
        $dominantAgeRange = $ageGroups->isNotEmpty() ? $ageGroups->sortDesc()->keys()->first() : 'Non renseigné';

        // Reliability per city
        $reliability = [];
        $cities = Qualification::getCities();
        foreach ($cities as $cityKey => $cityName) {
            $count = $qualifications->where('city', $cityKey)->count();
            $reliability[$cityKey] = [
                'count' => $count,
                'level' => $this->getReliability($count),
                'label' => self::reliabilityLabel($this->getReliability($count)),
            ];
        }

        return [
            'total' => $total,
            'avgPerDay' => $avgPerDay,
            'internationalPct' => $internationalPct,
            'dominantProfile' => $dominantProfile,
            'dominantAgeRange' => $dominantAgeRange,
            'reliability' => $reliability,
        ];
    }

    /**
     * Compute % international visitors, with normalization support.
     */
    protected function computeInternationalPct(Collection $qualifications, ?string $city, string $mode): float
    {
        if ($mode === 'normalized' && (!$city || $city === 'all')) {
            $cities = array_keys(Qualification::getCities());
            $percentages = [];

            foreach ($cities as $cityKey) {
                $cityQuals = $qualifications->where('city', $cityKey);
                $cityTotal = $cityQuals->count();

                if ($cityTotal === 0) continue;

                $intl = $cityQuals->filter(fn($q) => !$this->isFranceOnly($q->form_data ?? []))->count();
                $percentages[] = ($intl / $cityTotal) * 100;
            }

            return count($percentages) > 0 ? round(array_sum($percentages) / count($percentages), 1) : 0;
        }

        $total = $qualifications->count();
        if ($total === 0) return 0;

        $intl = $qualifications->filter(fn($q) => !$this->isFranceOnly($q->form_data ?? []))->count();
        return round(($intl / $total) * 100, 1);
    }

    // ──────────── G3: General Demands ────────────

    public function getGeneralDemands(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        // Build per-city data + qualification counts (for correct normalization denominator)
        $dataByCities = [];
        $qualCountsByCity = [];
        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $qualCountsByCity[$cityKey] = $cityQuals->count();
            $requests = $cityQuals->flatMap(fn($q) => $q->form_data['generalRequests'] ?? [])->countBy()->toArray();
            $dataByCities[$cityKey] = $requests;
        }

        if ($mode === 'normalized' && count($cities) > 1) {
            // Pass qualification counts as denominator (not sum of demand occurrences)
            $result = $this->normalize($dataByCities, $qualCountsByCity);
            return [
                'labels' => array_keys($result['normalized']),
                'values' => array_values($result['normalized']),
                'perCity' => $result['perCity'],
                'perCityPct' => $result['perCityPct'],
                'cityTotals' => $result['cityTotals'],
                'mode' => 'normalized',
            ];
        }

        // Absolute mode: sum across all cities
        $totals = [];
        foreach ($dataByCities as $items) {
            foreach ($items as $label => $count) {
                $totals[$label] = ($totals[$label] ?? 0) + $count;
            }
        }
        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'values' => array_values($totals),
            'perCity' => $dataByCities,
            'perCityPct' => [],
            'cityTotals' => $qualCountsByCity,
            'mode' => 'absolute',
        ];
    }

    // ──────────── G4: Profile Distribution ────────────

    public function getProfileDistribution(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        // Build per-city data + qualification counts
        $dataByCities = [];
        $qualCountsByCity = [];
        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $qualCountsByCity[$cityKey] = $cityQuals->count();
            $profiles = $cityQuals->map(fn($q) => $q->form_data['profile'] ?? 'Non renseigné')
                ->countBy()
                ->toArray();
            $dataByCities[$cityKey] = $profiles;
        }

        if ($mode === 'normalized' && count($cities) > 1) {
            $result = $this->normalize($dataByCities, $qualCountsByCity);
            $grouped = $this->groupSmallValues($result['normalized'], 3.0);
            return [
                'labels' => array_keys($grouped),
                'values' => array_values($grouped),
                'perCity' => $result['perCity'],
                'perCityPct' => $result['perCityPct'],
                'cityTotals' => $result['cityTotals'],
                'mode' => 'normalized',
            ];
        }

        // Absolute mode
        $totals = [];
        foreach ($dataByCities as $items) {
            foreach ($items as $label => $count) {
                $totals[$label] = ($totals[$label] ?? 0) + $count;
            }
        }
        arsort($totals);

        // Group small values in absolute mode based on percentage of total
        $grandTotal = array_sum($totals);
        if ($grandTotal > 0) {
            $asPercentages = array_map(fn($v) => ($v / $grandTotal) * 100, $totals);
            $grouped = $this->groupSmallValues($asPercentages, 3.0);
            // Convert back to counts: Autre = total minus kept items
            $result = [];
            foreach ($grouped as $label => $pct) {
                if ($label === 'Autre') {
                    $keptSum = 0;
                    foreach ($grouped as $gl => $gp) {
                        if ($gl !== 'Autre' && isset($totals[$gl])) {
                            $keptSum += $totals[$gl];
                        }
                    }
                    $result[$label] = $grandTotal - $keptSum;
                } else {
                    $result[$label] = $totals[$label] ?? 0;
                }
            }
            $totals = $result;
        }

        return [
            'labels' => array_keys($totals),
            'values' => array_values($totals),
            'perCity' => $dataByCities,
            'perCityPct' => [],
            'cityTotals' => $qualCountsByCity,
            'mode' => 'absolute',
        ];
    }

    /**
     * Group values below threshold into "Autre".
     */
    protected function groupSmallValues(array $data, float $threshold): array
    {
        $kept = [];
        $otherSum = 0;

        foreach ($data as $label => $value) {
            if ($value < $threshold) {
                $otherSum += $value;
            } else {
                $kept[$label] = $value;
            }
        }

        arsort($kept);

        if ($otherSum > 0) {
            $kept['Autre'] = round($otherSum, 1);
        }

        return $kept;
    }

    // ──────────── G1: Temporal Evolution ────────────

    public function getTemporalEvolution(?string $city, ?string $startDate, ?string $endDate, string $granularity = 'auto'): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        // Determine granularity
        if ($granularity === 'auto') {
            $granularity = $this->autoGranularity($startDate, $endDate);
        }

        // Group qualifications by period and city
        $allPeriods = [];
        $dataByCities = [];

        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $grouped = $cityQuals->groupBy(fn($q) => $this->formatPeriod($q->created_at, $granularity));

            $dataByCities[$cityKey] = $grouped->map->count()->toArray();

            foreach (array_keys($dataByCities[$cityKey]) as $period) {
                $allPeriods[$period] = true;
            }
        }

        // Also compute total line
        $totalGrouped = $qualifications->groupBy(fn($q) => $this->formatPeriod($q->created_at, $granularity));
        $totalData = $totalGrouped->map->count()->toArray();
        foreach (array_keys($totalData) as $period) {
            $allPeriods[$period] = true;
        }

        // Sort periods
        $periods = array_keys($allPeriods);
        sort($periods);

        // Build datasets with 0 for missing periods
        $datasets = [];
        foreach ($cities as $cityKey) {
            $datasets[$cityKey] = array_map(fn($p) => $dataByCities[$cityKey][$p] ?? 0, $periods);
        }
        $totalLine = array_map(fn($p) => $totalData[$p] ?? 0, $periods);

        return [
            'labels' => $periods,
            'datasets' => $datasets,
            'total' => $totalLine,
            'granularity' => $granularity,
        ];
    }

    protected function autoGranularity(?string $startDate, ?string $endDate): string
    {
        if (!$startDate || !$endDate) return 'month';

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days <= 60) return 'day';
        if ($days <= 180) return 'week';
        return 'month';
    }

    protected function formatPeriod($date, string $granularity): string
    {
        $carbon = Carbon::parse($date);
        return match ($granularity) {
            'day' => $carbon->format('Y-m-d'),
            'week' => $carbon->startOfWeek()->format('Y-m-d'),
            'month' => $carbon->format('Y-m'),
            default => $carbon->format('Y-m-d'),
        };
    }

    // ──────────── G2: City Distribution ────────────

    public function getCityDistribution(?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications('all', $startDate, $endDate);
        $cityNames = Qualification::getCities();

        $counts = [];
        foreach ($cityNames as $cityKey => $cityName) {
            $counts[$cityName] = $qualifications->where('city', $cityKey)->count();
        }

        arsort($counts);

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    // ──────────── G5: Age Ranges ────────────

    public function getAgeRanges(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        $dataByCities = [];
        $qualCountsByCity = [];
        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $qualCountsByCity[$cityKey] = $cityQuals->count();
            $ages = $cityQuals->flatMap(fn($q) => $q->form_data['ageGroups'] ?? [])->countBy()->toArray();
            $dataByCities[$cityKey] = $ages;
        }

        if ($mode === 'normalized' && count($cities) > 1) {
            $result = $this->normalize($dataByCities, $qualCountsByCity);
            return [
                'labels' => array_keys($result['normalized']),
                'values' => array_values($result['normalized']),
                'perCity' => $result['perCity'],
                'perCityPct' => $result['perCityPct'],
                'cityTotals' => $result['cityTotals'],
                'mode' => 'normalized',
            ];
        }

        $totals = [];
        foreach ($dataByCities as $items) {
            foreach ($items as $label => $count) {
                $totals[$label] = ($totals[$label] ?? 0) + $count;
            }
        }
        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'values' => array_values($totals),
            'perCity' => $dataByCities,
            'perCityPct' => [],
            'cityTotals' => $qualCountsByCity,
            'mode' => 'absolute',
        ];
    }

    // ──────────── G6: Geographic Origin ────────────

    public function getGeographicOrigin(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        // France vs International split per city
        $franceByCities = [];
        $intlByCities = [];
        $qualCountsByCity = [];
        $topDepartmentsByCity = [];
        $topCountriesByCity = [];

        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $qualCountsByCity[$cityKey] = $cityQuals->count();

            $france = $cityQuals->filter(fn($q) => $this->isFranceOnly($q->form_data ?? []))->count();
            $intl = $qualCountsByCity[$cityKey] - $france;

            $franceByCities[$cityKey] = ['France' => $france];
            $intlByCities[$cityKey] = ['International' => $intl];

            // Departments (only for French-only visitors)
            $depts = $cityQuals
                ->filter(fn($q) => $this->isFranceOnly($q->form_data ?? []))
                ->filter(fn($q) => !($q->form_data['departmentUnknown'] ?? false))
                ->flatMap(fn($q) => $q->form_data['departments'] ?? [])
                ->countBy()->toArray();
            $topDepartmentsByCity[$cityKey] = $depts;

            // Countries (toutes les sélections internationales : on compte chaque
            // pays non-France individuellement, même si la qualification en contient
            // plusieurs).
            $countries = $cityQuals
                ->flatMap(function ($q) {
                    return array_values(array_filter(
                        $this->extractCountries($q->form_data ?? []),
                        fn($c) => $c !== 'France' && $c !== 'Inconnu'
                    ));
                })
                ->countBy()->toArray();
            $topCountriesByCity[$cityKey] = $countries;
        }

        // France/International donut
        if ($mode === 'normalized' && count($cities) > 1) {
            $splitData = [];
            foreach ($cities as $cityKey) {
                $splitData[$cityKey] = [
                    'France' => $franceByCities[$cityKey]['France'] ?? 0,
                    'International' => $intlByCities[$cityKey]['International'] ?? 0,
                ];
            }
            $splitResult = $this->normalize($splitData, $qualCountsByCity);
            $francePct = $splitResult['normalized']['France'] ?? 0;
            $intlPct = $splitResult['normalized']['International'] ?? 0;
        } else {
            $totalFrance = array_sum(array_column($franceByCities, 'France'));
            $totalIntl = array_sum(array_column($intlByCities, 'International'));
            $grandTotal = $totalFrance + $totalIntl;
            $francePct = $grandTotal > 0 ? round(($totalFrance / $grandTotal) * 100, 1) : 0;
            $intlPct = $grandTotal > 0 ? round(($totalIntl / $grandTotal) * 100, 1) : 0;
        }

        // Top departments (aggregate)
        $allDepts = [];
        foreach ($topDepartmentsByCity as $depts) {
            foreach ($depts as $dept => $count) {
                $allDepts[$dept] = ($allDepts[$dept] ?? 0) + $count;
            }
        }
        arsort($allDepts);
        $topDepartments = array_slice($allDepts, 0, 10, true);

        // Top countries (aggregate)
        $allCountries = [];
        foreach ($topCountriesByCity as $countries) {
            foreach ($countries as $country => $count) {
                $allCountries[$country] = ($allCountries[$country] ?? 0) + $count;
            }
        }
        arsort($allCountries);
        $topCountries = array_slice($allCountries, 0, 10, true);

        return [
            'francePct' => $francePct,
            'internationalPct' => $intlPct,
            'topDepartments' => ['labels' => array_keys($topDepartments), 'values' => array_values($topDepartments)],
            'topCountries' => ['labels' => array_keys($topCountries), 'values' => array_values($topCountries)],
            'mode' => $mode,
        ];
    }

    // ──────────── Âge moyen (page uniquement) ────────────

    /**
     * Âge moyen estimé des visiteurs.
     *
     * Les tranches d'âge sont converties en âge médian (ex. « 25-40 » => 32.5,
     * « 60+ » => 70) puis pondérées par le nombre d'occurrences. La tranche
     * « Inconnu » et les libellés non interprétables sont ignorés.
     * En mode normalisé multi-villes, on fait la moyenne des moyennes par ville
     * pour ne pas laisser un bureau à fort volume écraser les autres.
     */
    public function getAverageAge(?string $city, ?string $startDate, ?string $endDate, string $mode = 'absolute'): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        $perCity = [];
        $globalSum = 0.0;
        $globalCount = 0;

        foreach ($cities as $cityKey) {
            $sum = 0.0;
            $count = 0;

            foreach ($qualifications->where('city', $cityKey) as $qualification) {
                foreach ((array) ($qualification->form_data['ageGroups'] ?? []) as $range) {
                    $midpoint = $this->ageRangeMidpoint((string) $range);
                    if ($midpoint === null) {
                        continue;
                    }
                    $sum += $midpoint;
                    $count++;
                }
            }

            $perCity[$cityKey] = $count > 0 ? round($sum / $count, 1) : null;
            $globalSum += $sum;
            $globalCount += $count;
        }

        if ($globalCount === 0) {
            return ['average' => null, 'sampleSize' => 0, 'perCity' => $perCity, 'mode' => $mode];
        }

        if ($mode === 'normalized' && count($cities) > 1) {
            $cityAverages = array_filter($perCity, fn($avg) => $avg !== null);
            $average = count($cityAverages) > 0
                ? round(array_sum($cityAverages) / count($cityAverages), 1)
                : null;
        } else {
            $average = round($globalSum / $globalCount, 1);
        }

        return [
            'average' => $average,
            'sampleSize' => $globalCount,
            'perCity' => $perCity,
            'mode' => $mode,
        ];
    }

    /**
     * Convertit un libellé de tranche d'âge en âge médian.
     * « 25-40 » => 32.5, « 60+ » => 70, « Inconnu » => null.
     */
    protected function ageRangeMidpoint(string $range): ?float
    {
        $range = trim($range);

        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m)) {
            return ((int) $m[1] + (int) $m[2]) / 2;
        }

        // Tranche ouverte « 60+ » : on retient une espérance de 10 ans au-delà du seuil
        if (preg_match('/^(\d+)\s*\+$/', $range, $m)) {
            return (int) $m[1] + 10;
        }

        return null;
    }

    // ──────────── Répartition d'origine détaillée (page uniquement) ────────────

    /**
     * Répartition France / Europe (hors France) / Reste du monde, et part des
     * visiteurs PACA et Alpes-de-Haute-Provence rapportée au total des Français.
     *
     * Chaque qualification est classée une seule fois : « Reste du monde » si elle
     * contient au moins un pays hors Europe, sinon « Europe » si elle contient au
     * moins un pays européen hors France, sinon « France ».
     */
    public function getOriginBreakdown(?string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $european = array_flip(self::EUROPEAN_COUNTRIES);
        $pacaCodes = array_flip(self::PACA_DEPARTMENTS);

        $france = 0;
        $europe = 0;
        $world = 0;

        $frenchWithDepartment = 0;
        $paca = 0;
        $ahp = 0;

        foreach ($qualifications as $qualification) {
            $formData = $qualification->form_data ?? [];

            $countries = array_values(array_filter(
                $this->extractCountries($formData),
                fn($c) => $c !== 'Inconnu'
            ));
            $foreign = array_values(array_filter($countries, fn($c) => $c !== 'France'));

            if (empty($foreign)) {
                $france++;

                $departments = (array) ($formData['departments'] ?? []);
                if (($formData['departmentUnknown'] ?? false) || empty($departments)) {
                    continue;
                }

                $frenchWithDepartment++;
                $codes = array_filter(array_map(fn($d) => $this->departmentCode((string) $d), $departments));

                if (count(array_intersect_key($pacaCodes, array_flip($codes))) > 0) {
                    $paca++;
                }
                if (in_array(self::AHP_DEPARTMENT, $codes, true)) {
                    $ahp++;
                }

                continue;
            }

            if (count(array_filter($foreign, fn($c) => !isset($european[$c]))) > 0) {
                $world++;
            } else {
                $europe++;
            }
        }

        $total = $france + $europe + $world;
        $pct = fn(int $value, int $base) => $base > 0 ? round(($value / $base) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'france' => ['count' => $france, 'pct' => $pct($france, $total)],
            'europe' => ['count' => $europe, 'pct' => $pct($europe, $total)],
            'world' => ['count' => $world, 'pct' => $pct($world, $total)],
            'frenchWithDepartment' => $frenchWithDepartment,
            'paca' => ['count' => $paca, 'pct' => $pct($paca, $frenchWithDepartment)],
            'ahp' => ['count' => $ahp, 'pct' => $pct($ahp, $frenchWithDepartment)],
        ];
    }

    /**
     * Extrait le code d'un département stocké sous la forme « 04 - Alpes-de-Haute-Provence ».
     */
    protected function departmentCode(string $department): ?string
    {
        if (preg_match('/^\s*(\d{2,3}|2[AB])\b/i', $department, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    // ──────────── Type de visiteur (page uniquement) ────────────

    /**
     * Répartition en % des types de visiteur (Habitant / Socio Pro / Touriste).
     *
     * Le champ `visitorType` n'existe pas sur les anciens formulaires : comme dans
     * l'export, ces qualifications sont comptées en « Touriste » (valeur par défaut
     * historique). Le nombre de fiches concernées est renvoyé pour affichage.
     */
    public function getVisitorTypes(?string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);

        $counts = array_fill_keys(self::VISITOR_TYPES, 0);
        $legacyDefaulted = 0;
        $total = 0;

        foreach ($qualifications as $qualification) {
            $type = trim((string) ($qualification->form_data['visitorType'] ?? ''));

            if ($type === '') {
                $type = 'Touriste';
                $legacyDefaulted++;
            }

            if (!array_key_exists($type, $counts)) {
                $counts[$type] = 0;
            }

            $counts[$type]++;
            $total++;
        }

        $items = [];
        foreach ($counts as $label => $count) {
            $items[] = [
                'label' => $label,
                'count' => $count,
                'pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        return [
            'total' => $total,
            'legacyDefaulted' => $legacyDefaulted,
            'items' => $items,
        ];
    }

    // ──────────── G7: Contact Methods ────────────

    public function getContactMethods(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);
        $cities = $this->getActiveCities($city);

        $dataByCities = [];
        $qualCountsByCity = [];
        foreach ($cities as $cityKey) {
            $cityQuals = $qualifications->where('city', $cityKey);
            $qualCountsByCity[$cityKey] = $cityQuals->count();
            $methods = $cityQuals->map(fn($q) => $q->form_data['contactMethod'] ?? 'Non renseigné')
                ->countBy()->toArray();
            $dataByCities[$cityKey] = $methods;
        }

        if ($mode === 'normalized' && count($cities) > 1) {
            $result = $this->normalize($dataByCities, $qualCountsByCity);
            return [
                'labels' => array_keys($result['normalized']),
                'values' => array_values($result['normalized']),
                'perCity' => $result['perCity'],
                'perCityPct' => $result['perCityPct'],
                'cityTotals' => $result['cityTotals'],
                'mode' => 'normalized',
            ];
        }

        $totals = [];
        foreach ($dataByCities as $items) {
            foreach ($items as $label => $count) {
                $totals[$label] = ($totals[$label] ?? 0) + $count;
            }
        }
        arsort($totals);

        return [
            'labels' => array_keys($totals),
            'values' => array_values($totals),
            'perCity' => $dataByCities,
            'perCityPct' => [],
            'cityTotals' => $qualCountsByCity,
            'mode' => 'absolute',
        ];
    }

    // ──────────── G8: Agent Activity ────────────

    public function getAgentActivity(?string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);

        $userIds = $qualifications->pluck('user_id')->unique()->filter();
        $users = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id');

        $byAgent = $qualifications->groupBy('user_id')->map(function ($items, $userId) use ($users) {
            return [
                'name' => $users[$userId] ?? 'Inconnu',
                'count' => $items->count(),
            ];
        })->sortByDesc('count')->values()->toArray();

        return [
            'labels' => array_column($byAgent, 'name'),
            'values' => array_column($byAgent, 'count'),
        ];
    }

    // ──────────── G9: City-Specific Demands ────────────

    /**
     * Demandes spécifiques d'un bureau, ou agrégées sur toutes les villes
     * lorsque $city vaut 'all' / null.
     */
    public function getCitySpecificDemands(?string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);

        if ($city && $city !== 'all') {
            $qualifications = $qualifications->where('city', $city);
        }

        $specificRequests = $qualifications
            ->flatMap(fn($q) => $q->form_data['specificRequests'] ?? [])
            ->countBy()
            ->sortDesc()
            ->toArray();

        $otherSpecific = $qualifications
            ->flatMap(fn($q) => $q->form_data['otherSpecificRequests'] ?? [])
            ->countBy()
            ->sortDesc()
            ->toArray();

        return [
            'specific' => ['labels' => array_keys($specificRequests), 'values' => array_values($specificRequests)],
            'otherSpecific' => ['labels' => array_keys($otherSpecific), 'values' => array_values($otherSpecific)],
        ];
    }

    // ──────────── Cross-Tabulations ────────────

    /**
     * Build a cross-tabulation matrix.
     *
     * @param Collection $qualifications
     * @param string $rowField Key in form_data for rows (or 'city', 'month')
     * @param string $colField Key in form_data for columns (or 'city', 'month')
     * @param bool $rowIsMulti Whether row field is an array in form_data
     * @param bool $colIsMulti Whether col field is an array in form_data
     * @return array ['rows' => [...], 'cols' => [...], 'matrix' => [[...], ...], 'maxValue' => int]
     */
    public function buildCrossTab(
        Collection $qualifications,
        string $rowField,
        string $colField,
        bool $rowIsMulti = false,
        bool $colIsMulti = false
    ): array {
        $cityNames = Qualification::getCities();
        $matrix = [];
        $rowLabels = [];
        $colLabels = [];

        foreach ($qualifications as $q) {
            $rowValues = $this->extractCrossTabValues($q, $rowField, $rowIsMulti, $cityNames);
            $colValues = $this->extractCrossTabValues($q, $colField, $colIsMulti, $cityNames);

            foreach ($rowValues as $r) {
                foreach ($colValues as $c) {
                    $rowLabels[$r] = true;
                    $colLabels[$c] = true;
                    $matrix[$r][$c] = ($matrix[$r][$c] ?? 0) + 1;
                }
            }
        }

        $rows = array_keys($rowLabels);
        $cols = array_keys($colLabels);

        // Sort: for month fields, sort chronologically; otherwise by total descending
        if ($rowField === 'month') {
            sort($rows);
        } else {
            usort($rows, fn($a, $b) => array_sum($matrix[$b] ?? []) - array_sum($matrix[$a] ?? []));
        }

        if ($colField === 'month') {
            sort($cols);
        } else {
            usort($cols, function($a, $b) use ($matrix, $rows) {
                $sumA = array_sum(array_column(array_map(fn($r) => [$matrix[$r][$a] ?? 0], $rows), 0));
                $sumB = array_sum(array_column(array_map(fn($r) => [$matrix[$r][$b] ?? 0], $rows), 0));
                return $sumB - $sumA;
            });
        }

        // Build the final matrix array and find max value
        $maxValue = 0;
        $finalMatrix = [];
        foreach ($rows as $r) {
            $row = [];
            foreach ($cols as $c) {
                $val = $matrix[$r][$c] ?? 0;
                $row[] = $val;
                if ($val > $maxValue) $maxValue = $val;
            }
            $finalMatrix[] = $row;
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
            'matrix' => $finalMatrix,
            'maxValue' => $maxValue,
        ];
    }

    /**
     * Extract values from a qualification for cross-tab.
     */
    protected function extractCrossTabValues($qualification, string $field, bool $isMulti, array $cityNames): array
    {
        if ($field === 'city') {
            return [$cityNames[$qualification->city] ?? $qualification->city];
        }

        if ($field === 'month') {
            return [Carbon::parse($qualification->created_at)->format('Y-m')];
        }

        $value = $qualification->form_data[$field] ?? null;

        if ($isMulti) {
            return is_array($value) && count($value) > 0 ? $value : ['Non renseigné'];
        }

        return [$value ?? 'Non renseigné'];
    }

    /**
     * Get cross-tabulations (Phase 3a: 2 priority ones).
     */
    public function getCrossTabulations(?string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);

        return [
            'cityXdemand' => $this->buildCrossTab($qualifications, 'city', 'generalRequests', false, true),
            'monthXdemand' => $this->buildCrossTab($qualifications, 'month', 'generalRequests', false, true),
        ];
    }

    // ──────────── YoY Comparison ────────────

    /**
     * Compare current period KPIs with same period last year.
     */
    public function getYoYComparison(?string $city, ?string $startDate, ?string $endDate, string $mode): array
    {
        if (!$startDate || !$endDate) {
            return [];
        }

        $prevStart = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $prevEnd = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        // Clear cache to query previous period
        $prevCacheKey = $this->cacheKey;
        $prevCachedQuals = $this->cachedQualifications;
        $this->cacheKey = null;
        $this->cachedQualifications = null;

        $prevKpis = $this->getKPIs($city, $prevStart, $prevEnd, $mode);

        // Restore cache
        $this->cacheKey = $prevCacheKey;
        $this->cachedQualifications = $prevCachedQuals;

        $currentKpis = $this->getKPIs($city, $startDate, $endDate, $mode);

        $comparisons = [];

        // Total
        $comparisons['total'] = $this->computeYoYChange($currentKpis['total'], $prevKpis['total']);
        // Avg per day
        $comparisons['avgPerDay'] = $this->computeYoYChange($currentKpis['avgPerDay'], $prevKpis['avgPerDay']);
        // International %
        $comparisons['internationalPct'] = $this->computeYoYChange($currentKpis['internationalPct'], $prevKpis['internationalPct']);

        return $comparisons;
    }

    protected function computeYoYChange($current, $previous): array
    {
        if (!is_numeric($current) || !is_numeric($previous)) {
            return ['change' => null, 'direction' => 'neutral', 'pct' => null, 'previous' => $previous];
        }

        if ($previous == 0) {
            return [
                'change' => $current > 0 ? 'up' : 'neutral',
                'direction' => $current > 0 ? 'up' : 'neutral',
                'pct' => null,
                'previous' => 0,
            ];
        }

        $pct = round((($current - $previous) / $previous) * 100, 1);

        return [
            'direction' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral'),
            'pct' => $pct,
            'previous' => $previous,
        ];
    }
}
