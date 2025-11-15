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
    public function getStatsByCity($startDate = null, $endDate = null, $status = 'all')
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

            // Compter les qualifications complétées par utilisateur
            $byUser = $cityQualifications->groupBy('user_id')->map(function($userQuals) {
                $user = $userQuals->first()->user;
                return [
                    'user_name' => $user ? $user->name : 'Inconnu',
                    'count' => $userQuals->count(),
                ];
            })->values()->toArray();

            $stats[$cityKey] = [
                'name' => $cityName,
                'total' => $cityQualifications->count(),
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
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonth();
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
    public function getGeographicStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Pays
        $countries = $qualifications->map(function($q) {
            $country = $q->form_data['country'] ?? null;
            if ($country === 'Autre' && isset($q->form_data['otherCountry'])) {
                return $q->form_data['otherCountry'];
            }
            return $country;
        })->filter()->countBy()->sortDesc()->take(10);

        // Départements (seulement pour France)
        $departments = $qualifications->filter(function($q) {
            return ($q->form_data['country'] ?? null) === 'France' && !($q->form_data['departmentUnknown'] ?? false);
        })->flatMap(function($q) {
            return $q->form_data['departments'] ?? [];
        })->countBy()->sortDesc()->take(10);

        return [
            'countries' => $countries->toArray(),
            'departments' => $departments->toArray(),
        ];
    }

    /**
     * Get visitor profile statistics
     */
    public function getProfileStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Profils
        $profiles = $qualifications->pluck('form_data.profile')->filter()->countBy();

        // Tranches d'âge (avec gestion du multi-select)
        $ageGroups = $qualifications->flatMap(function($q) {
            return $q->form_data['ageGroups'] ?? [];
        })->countBy();

        return [
            'profiles' => $profiles->toArray(),
            'ageGroups' => $ageGroups->toArray(),
        ];
    }

    /**
     * Get demand statistics
     */
    public function getDemandStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $qualifications = $this->baseQuery($cities, $startDate, $endDate, $status)->get();

        // Demandes générales (top 10)
        $generalRequests = $qualifications->flatMap(function($q) {
            return $q->form_data['generalRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Demandes spécifiques par ville
        $specificRequests = [];
        if (!empty($cities)) {
            foreach ($cities as $city) {
                $cityRequests = $qualifications->where('city', $city)->flatMap(function($q) {
                    return $q->form_data['specificRequests'] ?? [];
                })->countBy()->sortDesc();

                $specificRequests[$city] = $cityRequests->toArray();
            }
        }

        // Top 10 des demandes spécifiques (toutes villes confondues)
        $topSpecificRequests = $qualifications->flatMap(function($q) {
            return $q->form_data['specificRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Autres demandes spécifiques (demandes croisées)
        $otherSpecificRequests = $qualifications->flatMap(function($q) {
            return $q->form_data['otherSpecificRequests'] ?? [];
        })->countBy()->sortDesc()->take(10);

        // Textes libres (pour nuage de mots)
        $otherRequests = $qualifications
            ->pluck('form_data.otherRequest')
            ->filter()
            ->values();

        return [
            'generalRequests' => $generalRequests->toArray(),
            'specificRequests' => $specificRequests,
            'topSpecificRequests' => $topSpecificRequests->toArray(),
            'otherSpecificRequests' => $otherSpecificRequests->toArray(),
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
}
