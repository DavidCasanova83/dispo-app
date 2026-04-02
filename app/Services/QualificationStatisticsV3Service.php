<?php

namespace App\Services;

use App\Models\Qualification;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class QualificationStatisticsV3Service
{
    protected ?Collection $cachedQualifications = null;
    protected ?string $cacheKey = null;

    /**
     * Load and cache qualifications for the current request.
     */
    public function getQualifications(?string $city, ?string $startDate, ?string $endDate): Collection
    {
        $key = md5(($city ?? 'all') . ($startDate ?? '') . ($endDate ?? ''));

        if ($this->cacheKey === $key && $this->cachedQualifications !== null) {
            return $this->cachedQualifications;
        }

        $query = $this->baseQuery($city, $startDate, $endDate);
        $this->cachedQualifications = $query->select(['id', 'city', 'form_data', 'user_id', 'created_at'])->get();
        $this->cacheKey = $key;

        return $this->cachedQualifications;
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

                $intl = $cityQuals->filter(fn($q) => ($q->form_data['country'] ?? 'France') !== 'France')->count();
                $percentages[] = ($intl / $cityTotal) * 100;
            }

            return count($percentages) > 0 ? round(array_sum($percentages) / count($percentages), 1) : 0;
        }

        $total = $qualifications->count();
        if ($total === 0) return 0;

        $intl = $qualifications->filter(fn($q) => ($q->form_data['country'] ?? 'France') !== 'France')->count();
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

            $france = $cityQuals->filter(fn($q) => ($q->form_data['country'] ?? 'France') === 'France')->count();
            $intl = $qualCountsByCity[$cityKey] - $france;

            $franceByCities[$cityKey] = ['France' => $france];
            $intlByCities[$cityKey] = ['International' => $intl];

            // Departments (only for French visitors)
            $depts = $cityQuals
                ->filter(fn($q) => ($q->form_data['country'] ?? 'France') === 'France')
                ->filter(fn($q) => !($q->form_data['departmentUnknown'] ?? false))
                ->flatMap(fn($q) => $q->form_data['departments'] ?? [])
                ->countBy()->toArray();
            $topDepartmentsByCity[$cityKey] = $depts;

            // Countries (only for international)
            $countries = $cityQuals
                ->filter(fn($q) => ($q->form_data['country'] ?? 'France') !== 'France')
                ->map(function($q) {
                    $country = $q->form_data['country'] ?? 'Non renseigné';
                    if ($country === 'Autre' && isset($q->form_data['otherCountry'])) {
                        return $q->form_data['otherCountry'];
                    }
                    return $country;
                })->countBy()->toArray();
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

    public function getCitySpecificDemands(string $city, ?string $startDate, ?string $endDate): array
    {
        $qualifications = $this->getQualifications($city, $startDate, $endDate);

        $specificRequests = $qualifications->where('city', $city)
            ->flatMap(fn($q) => $q->form_data['specificRequests'] ?? [])
            ->countBy()
            ->sortDesc()
            ->toArray();

        $otherSpecific = $qualifications->where('city', $city)
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
