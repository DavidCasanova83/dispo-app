<?php

namespace App\Exports;

use App\Exports\Sheets\StatisticsSheet;
use App\Models\Qualification;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QualificationsV3Export implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $cityNames = Qualification::getCities();
        $sheets = [];

        // 1. KPIs
        $kpis = $this->data['kpis'];
        $sheets[] = new StatisticsSheet('KPIs', ['Indicateur', 'Valeur'], [
            ['Total qualifications', $kpis['total']],
            ['Moyenne par jour', $kpis['avgPerDay']],
            ['% visiteurs internationaux', $kpis['internationalPct'] . '%'],
            ['Profil dominant', $kpis['dominantProfile']],
            ['Tranche d\'âge dominante', $kpis['dominantAgeRange']],
        ]);

        // 2. City Distribution
        $cd = $this->data['cityDistribution'];
        $cdRows = [];
        for ($i = 0; $i < count($cd['labels']); $i++) {
            $cdRows[] = [$cd['labels'][$i], $cd['values'][$i]];
        }
        $sheets[] = new StatisticsSheet('Répartition villes', ['Ville', 'Qualifications'], $cdRows);

        // 3. General Demands - Absolute
        $sheets[] = $this->buildLabelValueSheet(
            'Demandes (absolu)',
            $this->data['generalDemands'],
            'Demande',
            $cityNames
        );

        // 4. General Demands - Normalized
        if (isset($this->data['generalDemandsNorm']) && $this->data['generalDemandsNorm']['mode'] === 'normalized') {
            $sheets[] = $this->buildNormalizedSheet(
                'Demandes (normalisé)',
                $this->data['generalDemandsNorm'],
                'Demande',
                $cityNames
            );
        }

        // 5. Profiles - Absolute
        $sheets[] = $this->buildLabelValueSheet(
            'Profils (absolu)',
            $this->data['profiles'],
            'Profil',
            $cityNames
        );

        // 6. Profiles - Normalized
        if (isset($this->data['profilesNorm']) && $this->data['profilesNorm']['mode'] === 'normalized') {
            $sheets[] = $this->buildNormalizedSheet(
                'Profils (normalisé)',
                $this->data['profilesNorm'],
                'Profil',
                $cityNames
            );
        }

        // 7. Age Ranges - Absolute
        $sheets[] = $this->buildLabelValueSheet(
            'Âges (absolu)',
            $this->data['ageRanges'],
            'Tranche d\'âge',
            $cityNames
        );

        // 8. Age Ranges - Normalized
        if (isset($this->data['ageRangesNorm']) && $this->data['ageRangesNorm']['mode'] === 'normalized') {
            $sheets[] = $this->buildNormalizedSheet(
                'Âges (normalisé)',
                $this->data['ageRangesNorm'],
                'Tranche d\'âge',
                $cityNames
            );
        }

        // 9. Geographic
        $geo = $this->data['geographic'];
        $geoRows = [
            ['France', $geo['francePct'] . '%'],
            ['International', $geo['internationalPct'] . '%'],
        ];
        if (isset($geo['topDepartments'])) {
            $geoRows[] = ['', ''];
            $geoRows[] = ['--- Top Départements ---', ''];
            for ($i = 0; $i < count($geo['topDepartments']['labels']); $i++) {
                $geoRows[] = [$geo['topDepartments']['labels'][$i], $geo['topDepartments']['values'][$i]];
            }
        }
        if (isset($geo['topCountries'])) {
            $geoRows[] = ['', ''];
            $geoRows[] = ['--- Top Pays ---', ''];
            for ($i = 0; $i < count($geo['topCountries']['labels']); $i++) {
                $geoRows[] = [$geo['topCountries']['labels'][$i], $geo['topCountries']['values'][$i]];
            }
        }
        $sheets[] = new StatisticsSheet('Géographie', ['Origine', 'Valeur'], $geoRows);

        // 10. Contact Methods
        $cm = $this->data['contactMethods'];
        $cmRows = [];
        for ($i = 0; $i < count($cm['labels']); $i++) {
            $cmRows[] = [$cm['labels'][$i], $cm['values'][$i]];
        }
        $sheets[] = new StatisticsSheet('Contact', ['Méthode', 'Nombre'], $cmRows);

        // 11. Agent Activity
        $aa = $this->data['agentActivity'];
        $aaRows = [];
        for ($i = 0; $i < count($aa['labels']); $i++) {
            $aaRows[] = [$aa['labels'][$i], $aa['values'][$i]];
        }
        $sheets[] = new StatisticsSheet('Agents', ['Agent', 'Qualifications'], $aaRows);

        return $sheets;
    }

    /**
     * Build a sheet with label + total + per-city breakdown (absolute).
     */
    protected function buildLabelValueSheet(string $title, array $data, string $labelHeader, array $cityNames): StatisticsSheet
    {
        $headings = [$labelHeader, 'Total'];
        $hasCities = !empty($data['perCity']);

        if ($hasCities) {
            foreach ($cityNames as $cityName) {
                $headings[] = $cityName;
            }
        }

        $rows = [];
        for ($i = 0; $i < count($data['labels']); $i++) {
            $row = [$data['labels'][$i], $data['values'][$i]];
            if ($hasCities) {
                foreach (array_keys($cityNames) as $cityKey) {
                    $row[] = $data['perCity'][$cityKey][$data['labels'][$i]] ?? 0;
                }
            }
            $rows[] = $row;
        }

        return new StatisticsSheet($title, $headings, $rows);
    }

    /**
     * Build a sheet for normalized data with per-city percentages.
     */
    protected function buildNormalizedSheet(string $title, array $data, string $labelHeader, array $cityNames): StatisticsSheet
    {
        $headings = [$labelHeader, 'Moyenne normalisée (%)'];
        foreach ($cityNames as $cityName) {
            $headings[] = $cityName . ' (%)';
        }

        $rows = [];
        for ($i = 0; $i < count($data['labels']); $i++) {
            $label = $data['labels'][$i];
            $row = [$label, $data['values'][$i]];
            foreach (array_keys($cityNames) as $cityKey) {
                $row[] = $data['perCityPct'][$cityKey][$label] ?? 0;
            }
            $rows[] = $row;
        }

        return new StatisticsSheet($title, $headings, $rows);
    }
}
