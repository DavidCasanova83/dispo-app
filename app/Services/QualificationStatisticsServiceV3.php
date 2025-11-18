<?php

namespace App\Services;

use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service de statistiques avec pondération par ville (V3)
 *
 * Ce service calcule les statistiques en donnant le même poids à chaque ville,
 * indépendamment du nombre d'entrées, pour éviter que les villes avec plus de
 * données dominent les résultats globaux.
 */
class QualificationStatisticsServiceV3
{
    /**
     * Get KPIs overview avec pondération par ville
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
     * Get geographic origin statistics AVEC PONDÉRATION PAR VILLE
     * Chaque ville a le même poids dans le calcul
     */
    public function getGeographicStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        $weightedCountries = [];
        $weightedDepartments = [];

        // Pour chaque ville, calculer les pourcentages locaux
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->baseQuery([$cityKey], $startDate, $endDate, $status)->get();

            if ($qualifications->isEmpty()) {
                continue;
            }

            $cityTotal = $qualifications->count();

            // Pays pour cette ville
            $cityCountries = $qualifications->map(function($q) {
                $country = $q->form_data['country'] ?? null;
                if ($country === 'Autre' && isset($q->form_data['otherCountry'])) {
                    return $q->form_data['otherCountry'];
                }
                return $country;
            })->filter()->countBy();

            // Ajouter avec pondération (chaque ville compte pour 1)
            foreach ($cityCountries as $country => $count) {
                $percentage = ($count / $cityTotal) * 100;
                $weightedCountries[$country] = ($weightedCountries[$country] ?? 0) + $percentage;
            }

            // Départements pour cette ville (seulement France)
            $cityDepartments = $qualifications->filter(function($q) {
                return ($q->form_data['country'] ?? null) === 'France' && !($q->form_data['departmentUnknown'] ?? false);
            })->flatMap(function($q) {
                return $q->form_data['departments'] ?? [];
            })->countBy();

            $franceDepartmentsTotal = $qualifications->filter(function($q) {
                return ($q->form_data['country'] ?? null) === 'France' && !($q->form_data['departmentUnknown'] ?? false);
            })->count();

            if ($franceDepartmentsTotal > 0) {
                foreach ($cityDepartments as $dept => $count) {
                    $percentage = ($count / $franceDepartmentsTotal) * 100;
                    $weightedDepartments[$dept] = ($weightedDepartments[$dept] ?? 0) + $percentage;
                }
            }
        }

        // Normaliser par le nombre de villes
        $nbCities = count($citiesToAnalyze);
        if ($nbCities > 0) {
            foreach ($weightedCountries as $country => $value) {
                $weightedCountries[$country] = round($value / $nbCities, 2);
            }
            foreach ($weightedDepartments as $dept => $value) {
                $weightedDepartments[$dept] = round($value / $nbCities, 2);
            }
        }

        // Trier et prendre le top 10
        arsort($weightedCountries);
        $weightedCountries = array_slice($weightedCountries, 0, 10, true);

        arsort($weightedDepartments);
        $weightedDepartments = array_slice($weightedDepartments, 0, 10, true);

        return [
            'countries' => $weightedCountries,
            'departments' => $weightedDepartments,
        ];
    }

    /**
     * Get visitor profile statistics AVEC PONDÉRATION PAR VILLE
     */
    public function getProfileStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        $weightedProfiles = [];
        $weightedAgeGroups = [];

        // Pour chaque ville, calculer les pourcentages locaux
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->baseQuery([$cityKey], $startDate, $endDate, $status)->get();

            if ($qualifications->isEmpty()) {
                continue;
            }

            $cityTotal = $qualifications->count();

            // Profils pour cette ville
            $cityProfiles = $qualifications->pluck('form_data.profile')->filter()->countBy();

            foreach ($cityProfiles as $profile => $count) {
                $percentage = ($count / $cityTotal) * 100;
                $weightedProfiles[$profile] = ($weightedProfiles[$profile] ?? 0) + $percentage;
            }

            // Tranches d'âge pour cette ville
            $cityAgeGroups = $qualifications->flatMap(function($q) {
                return $q->form_data['ageGroups'] ?? [];
            })->countBy();

            // Compter le total d'entrées avec des tranches d'âge pour normaliser
            $ageGroupEntries = $qualifications->filter(function($q) {
                return !empty($q->form_data['ageGroups'] ?? []);
            })->count();

            if ($ageGroupEntries > 0) {
                foreach ($cityAgeGroups as $ageGroup => $count) {
                    $percentage = ($count / $ageGroupEntries) * 100;
                    $weightedAgeGroups[$ageGroup] = ($weightedAgeGroups[$ageGroup] ?? 0) + $percentage;
                }
            }
        }

        // Normaliser par le nombre de villes
        $nbCities = count($citiesToAnalyze);
        if ($nbCities > 0) {
            foreach ($weightedProfiles as $profile => $value) {
                $weightedProfiles[$profile] = round($value / $nbCities, 2);
            }
            foreach ($weightedAgeGroups as $ageGroup => $value) {
                $weightedAgeGroups[$ageGroup] = round($value / $nbCities, 2);
            }
        }

        return [
            'profiles' => $weightedProfiles,
            'ageGroups' => $weightedAgeGroups,
        ];
    }

    /**
     * Get demand statistics AVEC PONDÉRATION PAR VILLE
     */
    public function getDemandStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        $weightedGeneralRequests = [];
        $weightedTopSpecificRequests = [];
        $weightedOtherSpecificRequests = [];
        $allOtherRequests = [];
        $specificRequestsByCity = [];

        // Pour chaque ville, calculer les pourcentages locaux
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->baseQuery([$cityKey], $startDate, $endDate, $status)->get();

            if ($qualifications->isEmpty()) {
                continue;
            }

            // Demandes générales
            $generalRequestsCount = $qualifications->filter(function($q) {
                return !empty($q->form_data['generalRequests'] ?? []);
            })->count();

            if ($generalRequestsCount > 0) {
                $cityGeneralRequests = $qualifications->flatMap(function($q) {
                    return $q->form_data['generalRequests'] ?? [];
                })->countBy();

                foreach ($cityGeneralRequests as $request => $count) {
                    $percentage = ($count / $generalRequestsCount) * 100;
                    $weightedGeneralRequests[$request] = ($weightedGeneralRequests[$request] ?? 0) + $percentage;
                }
            }

            // Demandes spécifiques par ville (pour le graphique par ville)
            $citySpecificRequests = $qualifications->flatMap(function($q) {
                return $q->form_data['specificRequests'] ?? [];
            })->countBy()->sortDesc();

            $specificRequestsByCity[$cityKey] = $citySpecificRequests->toArray();

            // Top demandes spécifiques (toutes villes)
            $specificRequestsCount = $qualifications->filter(function($q) {
                return !empty($q->form_data['specificRequests'] ?? []);
            })->count();

            if ($specificRequestsCount > 0) {
                $cityTopSpecificRequests = $qualifications->flatMap(function($q) {
                    return $q->form_data['specificRequests'] ?? [];
                })->countBy();

                foreach ($cityTopSpecificRequests as $request => $count) {
                    $percentage = ($count / $specificRequestsCount) * 100;
                    $weightedTopSpecificRequests[$request] = ($weightedTopSpecificRequests[$request] ?? 0) + $percentage;
                }
            }

            // Autres demandes spécifiques (croisées)
            $otherSpecificRequestsCount = $qualifications->filter(function($q) {
                return !empty($q->form_data['otherSpecificRequests'] ?? []);
            })->count();

            if ($otherSpecificRequestsCount > 0) {
                $cityOtherSpecificRequests = $qualifications->flatMap(function($q) {
                    return $q->form_data['otherSpecificRequests'] ?? [];
                })->countBy();

                foreach ($cityOtherSpecificRequests as $request => $count) {
                    $percentage = ($count / $otherSpecificRequestsCount) * 100;
                    $weightedOtherSpecificRequests[$request] = ($weightedOtherSpecificRequests[$request] ?? 0) + $percentage;
                }
            }

            // Textes libres (on les garde tous)
            $otherRequests = $qualifications
                ->pluck('form_data.otherRequest')
                ->filter()
                ->values();

            $allOtherRequests = array_merge($allOtherRequests, $otherRequests->toArray());
        }

        // Normaliser par le nombre de villes
        $nbCities = count($citiesToAnalyze);
        if ($nbCities > 0) {
            foreach ($weightedGeneralRequests as $request => $value) {
                $weightedGeneralRequests[$request] = round($value / $nbCities, 2);
            }
            foreach ($weightedTopSpecificRequests as $request => $value) {
                $weightedTopSpecificRequests[$request] = round($value / $nbCities, 2);
            }
            foreach ($weightedOtherSpecificRequests as $request => $value) {
                $weightedOtherSpecificRequests[$request] = round($value / $nbCities, 2);
            }
        }

        // Trier et prendre le top 10
        arsort($weightedGeneralRequests);
        $weightedGeneralRequests = array_slice($weightedGeneralRequests, 0, 10, true);

        arsort($weightedTopSpecificRequests);
        $weightedTopSpecificRequests = array_slice($weightedTopSpecificRequests, 0, 10, true);

        arsort($weightedOtherSpecificRequests);
        $weightedOtherSpecificRequests = array_slice($weightedOtherSpecificRequests, 0, 10, true);

        return [
            'generalRequests' => $weightedGeneralRequests,
            'specificRequests' => $specificRequestsByCity,
            'topSpecificRequests' => $weightedTopSpecificRequests,
            'otherSpecificRequests' => $weightedOtherSpecificRequests,
            'otherRequests' => $allOtherRequests,
        ];
    }

    /**
     * Get contact data statistics AVEC PONDÉRATION PAR VILLE
     */
    public function getContactStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        $totalEmailRate = 0;
        $totalNewsletterRate = 0;
        $weightedContactMethods = [];
        $totalEmailProvided = 0;
        $totalNewsletterAccepted = 0;
        $totalEntries = 0;

        // Pour chaque ville, calculer les taux locaux
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->baseQuery([$cityKey], $startDate, $endDate, $status)->get();

            if ($qualifications->isEmpty()) {
                continue;
            }

            $cityTotal = $qualifications->count();
            $totalEntries += $cityTotal;

            // Emails fournis
            $emailProvided = $qualifications->filter(function($q) {
                return !empty($q->form_data['email'] ?? null);
            })->count();
            $totalEmailProvided += $emailProvided;

            $emailRate = $cityTotal > 0 ? ($emailProvided / $cityTotal) * 100 : 0;
            $totalEmailRate += $emailRate;

            // Newsletter
            $newsletterAccepted = $qualifications->filter(function($q) {
                return !empty($q->form_data['email'] ?? null) && ($q->form_data['consentNewsletter'] ?? false);
            })->count();
            $totalNewsletterAccepted += $newsletterAccepted;

            $newsletterRate = $emailProvided > 0 ? ($newsletterAccepted / $emailProvided) * 100 : 0;
            $totalNewsletterRate += $newsletterRate;

            // Méthodes de contact
            $cityContactMethods = $qualifications->pluck('form_data.contactMethod')->filter()->countBy();

            foreach ($cityContactMethods as $method => $count) {
                $percentage = ($count / $cityTotal) * 100;
                $weightedContactMethods[$method] = ($weightedContactMethods[$method] ?? 0) + $percentage;
            }
        }

        // Normaliser par le nombre de villes
        $nbCities = count($citiesToAnalyze);
        $avgEmailRate = $nbCities > 0 ? round($totalEmailRate / $nbCities, 1) : 0;
        $avgNewsletterRate = $nbCities > 0 ? round($totalNewsletterRate / $nbCities, 1) : 0;

        if ($nbCities > 0) {
            foreach ($weightedContactMethods as $method => $value) {
                $weightedContactMethods[$method] = round($value / $nbCities, 2);
            }
        }

        return [
            'emailProvided' => $totalEmailProvided,
            'emailRate' => $avgEmailRate,
            'newsletterAccepted' => $totalNewsletterAccepted,
            'newsletterRate' => $avgNewsletterRate,
            'contactMethods' => $weightedContactMethods,
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
