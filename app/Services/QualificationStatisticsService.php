<?php

namespace App\Services;

use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QualificationStatisticsService
{
    /**
     * Get KPIs overview
     */
    public function getKPIs(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $query = $this->baseQuery($cities, $startDate, $endDate, $status);

        $total = $query->count();
        $completed = $query->where('completed', true)->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // Aujourd'hui
        $today = $this->baseQuery($cities, now()->startOfDay(), now()->endOfDay(), $status)->count();

        // Cette semaine
        $thisWeek = $this->baseQuery($cities, now()->startOfWeek(), now()->endOfWeek(), $status)->count();

        // Période précédente pour la croissance
        $daysDiff = $startDate && $endDate ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) : 30;
        $previousStart = $startDate ? Carbon::parse($startDate)->subDays($daysDiff) : now()->subDays($daysDiff * 2);
        $previousEnd = $startDate ? Carbon::parse($startDate)->subDay() : now()->subDays($daysDiff);

        $currentPeriod = $this->baseQuery($cities, $startDate, $endDate, $status)->count();
        $previousPeriod = $this->baseQuery($cities, $previousStart, $previousEnd, $status)->count();

        $growth = $previousPeriod > 0 ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $total - $completed,
            'completionRate' => $completionRate,
            'today' => $today,
            'thisWeek' => $thisWeek,
            'growth' => $growth,
        ];
    }

    /**
     * Get statistics by city for comparison
     */
    public function getStatsByCity($startDate = null, $endDate = null, $status = 'all', $displayMode = 'normalized')
    {
        $cities = Qualification::getCities();
        $stats = [];

        // Récupérer toutes les qualifications complétées avec les utilisateurs
        $qualifications = Qualification::with('user')
            ->where('completed', true);

        if ($startDate) {
            $qualifications->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $qualifications->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $qualifications = $qualifications->get();

        // Grouper par ville et par utilisateur
        foreach ($cities as $cityKey => $cityName) {
            $cityQualifications = $qualifications->where('city', $cityKey);
            $cityTotal = $cityQualifications->count();

            // Compter les qualifications complétées par utilisateur
            $byUser = $cityQualifications->groupBy('user_id')->map(function($userQuals) use ($displayMode, $cityTotal) {
                $user = $userQuals->first()->user;
                $count = $userQuals->count();

                // Si mode normalisé, convertir en pourcentage
                $value = $displayMode === 'normalized' && $cityTotal > 0
                    ? round(($count / $cityTotal) * 100, 1)
                    : $count;

                return [
                    'user_name' => $user ? $user->name : 'Inconnu',
                    'count' => $count,  // Toujours garder le count absolu
                    'value' => $value,  // Valeur selon le mode
                ];
            })->values()->toArray();

            $stats[$cityKey] = [
                'name' => $cityName,
                'total' => $cityTotal,
                'byUser' => $byUser,
            ];
        }

        return $stats;
    }

    /**
     * Get temporal evolution data
     */
    public function getTemporalEvolution(array $cities = [], $startDate = null, $endDate = null, $status = 'all', $groupBy = 'day')
    {
        $startDate = $startDate ? Carbon::parse($startDate) :
            Carbon::parse(Qualification::min('created_at') ?? Carbon::now()->subYear());
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Détecter le driver de la base de données
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $dateFormat = match($groupBy) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%W',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m-%d',
            };
            $dateFormatSql = "strftime('{$dateFormat}', created_at)";
        } else {
            // MySQL/MariaDB
            $dateFormat = match($groupBy) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m-%d',
            };
            $dateFormatSql = "DATE_FORMAT(created_at, '{$dateFormat}')";
        }

        $query = $this->baseQuery($cities, $startDate, $endDate, $status);

        if (empty($cities)) {
            // Données globales
            $data = $query
                ->select(DB::raw("{$dateFormatSql} as period"), DB::raw('COUNT(*) as count'))
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            return ['global' => $data];
        } else {
            // Données par ville
            $result = [];
            foreach ($cities as $city) {
                $cityQuery = clone $query;
                $data = $cityQuery
                    ->where('city', $city)
                    ->select(DB::raw("{$dateFormatSql} as period"), DB::raw('COUNT(*) as count'))
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $result[$city] = $data;
            }
            return $result;
        }
    }

    /**
     * Get geographic origin statistics
     */
    public function getGeographicStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all', $displayMode = 'normalized')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Pays
        $countriesRaw = $qualifications->map(function($q) {
            $country = $q->form_data['country'] ?? null;
            if ($country === 'Autre' && isset($q->form_data['otherCountry'])) {
                return $q->form_data['otherCountry'];
            }
            return $country;
        })->filter()->countBy()->sortDesc()->take(10);

        // Départements (seulement pour France)
        $departmentsRaw = $qualifications->filter(function($q) {
            return ($q->form_data['country'] ?? null) === 'France' && !($q->form_data['departmentUnknown'] ?? false);
        })->flatMap(function($q) {
            return $q->form_data['departments'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Convertir en pourcentages si mode normalisé
        $countries = $this->convertToDisplayMode($countriesRaw->toArray(), $displayMode);
        $departments = $this->convertToDisplayMode($departmentsRaw->toArray(), $displayMode);

        return [
            'countries' => $countries,
            'departments' => $departments,
        ];
    }

    /**
     * Get visitor profile statistics
     */
    public function getProfileStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all', $displayMode = 'normalized')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Profils
        $profilesRaw = $qualifications->pluck('form_data.profile')->filter()->countBy();

        // Tranches d'âge (avec gestion du multi-select)
        $ageGroupsRaw = $qualifications->flatMap(function($q) {
            return $q->form_data['ageGroups'] ?? [];
        })->countBy();

        // Convertir en pourcentages si mode normalisé
        $profiles = $this->convertToDisplayMode($profilesRaw->toArray(), $displayMode);
        $ageGroups = $this->convertToDisplayMode($ageGroupsRaw->toArray(), $displayMode);

        return [
            'profiles' => $profiles,
            'ageGroups' => $ageGroups,
        ];
    }

    /**
     * Get demand statistics
     */
    public function getDemandStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all', $displayMode = 'normalized')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Demandes générales (top 10)
        $generalRequestsRaw = $qualifications->flatMap(function($q) {
            return $q->form_data['generalRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Demandes spécifiques par ville
        $specificRequests = [];
        if (!empty($cities)) {
            foreach ($cities as $city) {
                $cityRequestsRaw = $qualifications->where('city', $city)->flatMap(function($q) {
                    return $q->form_data['specificRequests'] ?? [];
                })->countBy()->sortDesc();

                $specificRequests[$city] = $this->convertToDisplayMode($cityRequestsRaw->toArray(), $displayMode);
            }
        }

        // Top 10 des demandes spécifiques (toutes villes confondues)
        $topSpecificRequestsRaw = $qualifications->flatMap(function($q) {
            return $q->form_data['specificRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Autres demandes spécifiques (demandes croisées)
        $otherSpecificRequestsRaw = $qualifications->flatMap(function($q) {
            return $q->form_data['otherSpecificRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Textes libres (pour nuage de mots)
        $otherRequests = $qualifications
            ->pluck('form_data.otherRequest')
            ->filter()
            ->values();

        return [
            'generalRequests' => $this->convertToDisplayMode($generalRequestsRaw->toArray(), $displayMode),
            'specificRequests' => $specificRequests,
            'topSpecificRequests' => $this->convertToDisplayMode($topSpecificRequestsRaw->toArray(), $displayMode),
            'otherSpecificRequests' => $this->convertToDisplayMode($otherSpecificRequestsRaw->toArray(), $displayMode),
            'otherRequests' => $otherRequests->toArray(),
        ];
    }

    /**
     * Get contact data statistics
     */
    public function getContactStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        $total = $qualifications->count();

        // Taux de fourniture d'email
        $emailProvided = $qualifications->filter(function($q) {
            return !empty($q->form_data['email'] ?? null);
        })->count();
        $emailRate = $total > 0 ? round(($emailProvided / $total) * 100, 1) : 0;

        // Taux d'acceptation newsletter (parmi ceux qui ont fourni un email)
        $newsletterAccepted = $qualifications->filter(function($q) {
            return !empty($q->form_data['email'] ?? null) && ($q->form_data['consentNewsletter'] ?? false);
        })->count();
        $newsletterRate = $emailProvided > 0 ? round(($newsletterAccepted / $emailProvided) * 100, 1) : 0;

        // Méthodes de contact
        $contactMethods = $qualifications->pluck('form_data.contactMethod')->filter()->countBy();

        return [
            'emailProvided' => $emailProvided,
            'emailRate' => $emailRate,
            'newsletterAccepted' => $newsletterAccepted,
            'newsletterRate' => $newsletterRate,
            'contactMethods' => $contactMethods->toArray(),
        ];
    }

    /**
     * Get city volumes with reliability indicators
     */
    public function getCityVolumes(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $allCities = Qualification::getCities();
        $volumes = [];

        // Si des villes spécifiques sont filtrées, utiliser uniquement celles-ci
        $citiesToProcess = !empty($cities) ? $cities : array_keys($allCities);

        foreach ($citiesToProcess as $cityKey) {
            $count = $this->baseQuery([$cityKey], $startDate, $endDate, $status)->count();

            // Calcul de la fiabilité selon la taille d'échantillon
            if ($count >= 100) {
                $reliability = 'high';
            } elseif ($count >= 30) {
                $reliability = 'medium';
            } else {
                $reliability = 'low';
            }

            // Calcul de la marge d'erreur (IC 95% pour proportion)
            // Formule : 1.96 × √(0.25 / n) × 100
            $marginError = $count > 0 ? round(1.96 * sqrt(0.25 / $count) * 100, 1) : 0;

            $volumes[$cityKey] = [
                'name' => $allCities[$cityKey] ?? $cityKey,
                'count' => $count,
                'reliability' => $reliability,
                'marginError' => $marginError,
            ];
        }

        return $volumes;
    }

    /**
     * Base query with filters
     */
    protected function baseQuery(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $query = Qualification::query();

        if (!empty($cities)) {
            $query->whereIn('city', $cities);
        }

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($status === 'completed') {
            $query->where('completed', true);
        } elseif ($status === 'incomplete') {
            $query->where('completed', false);
        }

        return $query;
    }

    /**
     * Convert counts to display mode (normalized or absolute)
     */
    protected function convertToDisplayMode(array $data, string $displayMode): array
    {
        if ($displayMode !== 'normalized' || empty($data)) {
            return $data;
        }

        $total = array_sum($data);
        if ($total === 0) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = round(($value / $total) * 100, 1);
        }

        return $result;
    }
}
