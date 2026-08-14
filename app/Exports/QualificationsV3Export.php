<?php

namespace App\Exports;

use App\Exports\Sheets\StatisticsSheet;
use App\Models\Qualification;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QualificationsV3Export implements WithMultipleSheets
{
    protected array $data;

    protected array $filters;

    public function __construct(array $data, array $filters = [])
    {
        $this->data = $data;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $cityNames = Qualification::getCities();
        $sheets = [];

        // 0. Listing complet des qualifications (une ligne par qualification)
        $sheets[] = new QualificationsExport($this->filters);

        // 1. KPIs (avec comparaison année précédente / YoY quand disponible)
        $kpis = $this->data['kpis'];
        $yoy = $this->data['yoy'] ?? [];
        $hasYoy = !empty($yoy);

        $kpiHeadings = ['Indicateur', 'Valeur'];
        if ($hasYoy) {
            $kpiHeadings[] = 'Année précédente';
            $kpiHeadings[] = 'Évolution';
        }

        $kpiRows = [
            $this->kpiRow('Total qualifications', $kpis['total'], $yoy['total'] ?? null, $hasYoy),
            $this->kpiRow('Moyenne par jour', $kpis['avgPerDay'], $yoy['avgPerDay'] ?? null, $hasYoy),
            $this->kpiRow('% visiteurs internationaux', $kpis['internationalPct'] . '%', $yoy['internationalPct'] ?? null, $hasYoy, '%'),
            $this->kpiRow('Profil dominant', $kpis['dominantProfile'], null, $hasYoy),
            $this->kpiRow('Tranche d\'âge dominante', $kpis['dominantAgeRange'], null, $hasYoy),
        ];
        $sheets[] = new StatisticsSheet('KPIs', $kpiHeadings, $kpiRows);

        // 2. Évolution temporelle (G1) : total + une colonne par ville
        if (isset($this->data['temporalEvolution'])) {
            $sheets[] = $this->buildTemporalSheet($this->data['temporalEvolution'], $cityNames);
        }

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

        // 12. Demandes spécifiques ville (G9) — seulement en vue ville unique
        if (isset($this->data['citySpecificDemands'])) {
            $csd = $this->data['citySpecificDemands'];
            $csdRows = [];
            foreach (['specific' => 'Demande spécifique', 'otherSpecific' => 'Autre demande spécifique'] as $key => $section) {
                $block = $csd[$key] ?? ['labels' => [], 'values' => []];
                if (!empty($block['labels'])) {
                    $csdRows[] = ['--- ' . $section . ' ---', ''];
                    for ($i = 0; $i < count($block['labels']); $i++) {
                        $csdRows[] = [$block['labels'][$i], $block['values'][$i]];
                    }
                }
            }
            $sheets[] = new StatisticsSheet('Demandes spécifiques', ['Demande', 'Nombre'], $csdRows);
        }

        // 13. Tableaux croisés / heatmaps
        if (isset($this->data['crossTabs']['cityXdemand'])) {
            $sheets[] = $this->buildCrossTabSheet(
                'Croisé villes x demandes',
                $this->data['crossTabs']['cityXdemand'],
                'Ville'
            );
        }
        if (isset($this->data['crossTabs']['monthXdemand'])) {
            $sheets[] = $this->buildCrossTabSheet(
                'Croisé mois x demandes',
                $this->data['crossTabs']['monthXdemand'],
                'Mois'
            );
        }

        return $sheets;
    }

    /**
     * Construit une ligne de KPI, avec colonnes YoY (valeur N-1 + évolution %) si demandé.
     */
    protected function kpiRow(string $label, $value, ?array $yoy, bool $hasYoy, string $suffix = ''): array
    {
        $row = [$label, $value];

        if (!$hasYoy) {
            return $row;
        }

        if ($yoy === null) {
            $row[] = '';
            $row[] = '';
            return $row;
        }

        $previous = $yoy['previous'] ?? null;
        $row[] = $previous !== null ? $previous . $suffix : '';

        if (isset($yoy['pct']) && $yoy['pct'] !== null) {
            $sign = $yoy['pct'] > 0 ? '+' : '';
            $row[] = $sign . $yoy['pct'] . '%';
        } else {
            $row[] = '';
        }

        return $row;
    }

    /**
     * Construit la feuille d'évolution temporelle : une ligne par période,
     * colonne Total puis une colonne par ville.
     */
    protected function buildTemporalSheet(array $data, array $cityNames): StatisticsSheet
    {
        $granularityLabels = ['day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois'];
        $periodHeader = $granularityLabels[$data['granularity'] ?? ''] ?? 'Période';

        $headings = [$periodHeader, 'Total'];
        foreach ($cityNames as $cityName) {
            $headings[] = $cityName;
        }

        $rows = [];
        $labels = $data['labels'] ?? [];
        foreach ($labels as $i => $period) {
            $row = [$period, $data['total'][$i] ?? 0];
            foreach (array_keys($cityNames) as $cityKey) {
                $row[] = $data['datasets'][$cityKey][$i] ?? 0;
            }
            $rows[] = $row;
        }

        return new StatisticsSheet('Évolution temporelle', $headings, $rows);
    }

    /**
     * Construit une feuille à partir d'un tableau croisé (heatmap) :
     * en-tête = libellé de ligne + colonnes, plus une colonne Total.
     */
    protected function buildCrossTabSheet(string $title, array $crossTab, string $rowHeader): StatisticsSheet
    {
        $cols = $crossTab['cols'] ?? [];
        $rowsData = $crossTab['rows'] ?? [];
        $matrix = $crossTab['matrix'] ?? [];

        $headings = array_merge([$rowHeader], $cols, ['Total']);

        $rows = [];
        foreach ($rowsData as $i => $rowLabel) {
            $line = $matrix[$i] ?? [];
            $rows[] = array_merge([$rowLabel], $line, [array_sum($line)]);
        }

        return new StatisticsSheet($title, $headings, $rows);
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
