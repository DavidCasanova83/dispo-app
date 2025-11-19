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
     * Cached qualifications data to avoid multiple DB queries
     * Multi-key cache: stores different datasets by cache key
     * @var array<string, \Illuminate\Support\Collection>
     */
    protected $cachedQualifications = [];

    /**
     * Current active cache key for filterByCity operations
     * @var string|null
     */
    protected $currentCacheKey = null;

    /**
     * Top N items limit for rankings
     */
    const TOP_N_LIMIT = 10;

    /**
     * Get KPIs overview
     *
     * Calcule les indicateurs clés de performance (KPIs) :
     * - Total des qualifications
     * - Taux de complétion
     * - Compteurs journaliers et hebdomadaires
     * - Croissance par rapport à la période précédente
     *
     * @param array $cities Villes à analyser (vide = toutes)
     * @param mixed $startDate Date de début (null = depuis le début)
     * @param mixed $endDate Date de fin (null = jusqu'à maintenant)
     * @param string $status Statut ('all', 'completed', 'incomplete')
     * @return array KPIs avec total, completed, completionRate, today, thisWeek, growth
     */
    public function getKPIs(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        // Charger les données principales une seule fois
        $qualifications = $this->loadQualificationsOnce($cities, $startDate, $endDate, $status);

        $total = $qualifications->count();
        $completed = $qualifications->where('completed', true)->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // Aujourd'hui - nouvelle requête car différentes dates
        $today = $this->baseQuery($cities, now()->startOfDay(), now()->endOfDay(), $status)->count();

        // Cette semaine - nouvelle requête car différentes dates
        $thisWeek = $this->baseQuery($cities, now()->startOfWeek(), now()->endOfWeek(), $status)->count();

        // Période précédente pour la croissance
        $daysDiff = $startDate && $endDate ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) : 30;
        $previousStart = $startDate ? Carbon::parse($startDate)->subDays($daysDiff) : now()->subDays($daysDiff * 2);
        $previousEnd = $startDate ? Carbon::parse($startDate)->subDay() : now()->subDays($daysDiff);

        $currentPeriod = $total; // Utilise le total déjà calculé
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
     *
     * Retourne les statistiques brutes par ville sans pondération :
     * - Nombre total de qualifications par ville
     * - Répartition par utilisateur dans chaque ville
     * Utile pour comparer la performance entre villes
     *
     * @param mixed $startDate Date de début (null = depuis le début)
     * @param mixed $endDate Date de fin (null = jusqu'à maintenant)
     * @param string $status Statut du filtre ('all', 'completed', 'incomplete')
     * @return array Stats par ville avec name, total, byUser
     */
    public function getStatsByCity($startDate = null, $endDate = null, $status = 'all')
    {
        $cities = Qualification::getCities();
        $stats = [];

        // Utiliser baseQuery() pour centraliser les filtres + charger la relation user
        $qualifications = $this->baseQuery([], $startDate, $endDate, $status)
            ->with('user')
            ->get();

        // Grouper par ville et par utilisateur
        foreach ($cities as $cityKey => $cityName) {
            $cityQualifications = $qualifications->where('city', $cityKey);

            // Compter les qualifications par utilisateur
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
     *
     * Retourne l'évolution temporelle des qualifications sans pondération :
     * - Groupement par heure, jour, semaine, mois ou année
     * - Données globales ou par ville
     * - Compatible MySQL/MariaDB et SQLite
     *
     * @param array $cities Villes à analyser (vide = global)
     * @param mixed $startDate Date de début (null = depuis la première qualification)
     * @param mixed $endDate Date de fin (null = jusqu'à maintenant)
     * @param string $status Statut du filtre
     * @param string $groupBy Groupement : 'hour', 'day', 'week', 'month', 'year'
     * @return array Données temporelles (global ou par ville)
     */
    public function getTemporalEvolution(array $cities = [], $startDate = null, $endDate = null, $status = 'all', $groupBy = 'day')
    {
        $startDate = $startDate ? Carbon::parse($startDate) :
            Carbon::parse(Qualification::min('created_at') ?? Carbon::now()->subYear());
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Obtenir la formule SQL pour le groupement temporel
        $dateFormatSql = $this->getDateFormatSql($groupBy);

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
     *
     * Calcule la répartition géographique des visiteurs :
     * - Pays d'origine (top 10)
     * - Départements français (top 10)
     * Chaque ville a le même poids dans le calcul pour éviter les biais
     *
     * @param array $cities Villes à analyser (vide = toutes)
     * @param mixed $startDate Date de début
     * @param mixed $endDate Date de fin
     * @param string $status Statut du filtre
     * @return array ['countries' => array, 'departments' => array]
     */
    public function getGeographicStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        // Charger les données une fois
        $this->loadQualificationsOnce($cities, $startDate, $endDate, $status);

        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        // Calculer les pays avec pondération
        $weightedCountries = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            function($qualifications) {
                return $qualifications
                    ->map(fn($q) => $this->extractCountry($q))
                    ->filter()
                    ->countBy();
            }
        );

        // Calculer les départements avec pondération (seulement France, sans departmentUnknown)
        $weightedDepartments = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            function($qualifications) {
                return $qualifications
                    ->filter(fn($q) => $this->isFrance($q))
                    ->flatMap(fn($q) => $this->extractDepartments($q))
                    ->countBy();
            },
            function($qualifications) {
                // Dénominateur : nombre de qualifications France avec départements connus
                return $qualifications->filter(function($q) {
                    return $this->isFrance($q) && !$this->fd($q, 'departmentUnknown', false);
                })->count();
            }
        );

        return [
            'countries' => $this->getTopN($weightedCountries),
            'departments' => $this->getTopN($weightedDepartments),
        ];
    }

    /**
     * Get visitor profile statistics AVEC PONDÉRATION PAR VILLE
     *
     * Calcule les profils des visiteurs :
     * - Types de profils (touriste, résident, etc.)
     * - Tranches d'âge
     * Chaque ville a le même poids dans le calcul
     *
     * @param array $cities Villes à analyser (vide = toutes)
     * @param mixed $startDate Date de début
     * @param mixed $endDate Date de fin
     * @param string $status Statut du filtre
     * @return array ['profiles' => array, 'ageGroups' => array]
     */
    public function getProfileStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        // Charger les données une fois
        $this->loadQualificationsOnce($cities, $startDate, $endDate, $status);

        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        // Calculer les profils avec pondération
        $weightedProfiles = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            function($qualifications) {
                return $qualifications
                    ->map(fn($q) => $this->fd($q, 'profile'))
                    ->filter()
                    ->countBy();
            }
        );

        // Calculer les tranches d'âge avec pondération
        $weightedAgeGroups = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            fn($qualifications) => $this->countFromArrayField($qualifications, 'ageGroups'),
            function($qualifications) {
                // Dénominateur : nombre de qualifications avec au moins une tranche d'âge
                return $qualifications->filter(fn($q) => !empty($this->fd($q, 'ageGroups', [])))->count();
            }
        );

        return [
            'profiles' => $weightedProfiles,
            'ageGroups' => $weightedAgeGroups,
        ];
    }

    /**
     * Get demand statistics AVEC PONDÉRATION PAR VILLE
     *
     * Calcule les demandes des visiteurs :
     * - Demandes générales (top 10 pondéré)
     * - Demandes spécifiques (par ville + top 10 global pondéré)
     * - Autres demandes spécifiques (top 10 pondéré)
     * - Demandes en texte libre (toutes)
     * Chaque ville a le même poids dans le calcul
     *
     * @param array $cities Villes à analyser (vide = toutes)
     * @param mixed $startDate Date de début
     * @param mixed $endDate Date de fin
     * @param string $status Statut du filtre
     * @return array ['generalRequests', 'specificRequests', 'topSpecificRequests', 'otherSpecificRequests', 'otherRequests']
     */
    public function getDemandStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        // Charger les données une fois
        $this->loadQualificationsOnce($cities, $startDate, $endDate, $status);

        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        // Demandes générales (pondérées)
        $weightedGeneralRequests = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            fn($qualifications) => $this->countFromArrayField($qualifications, 'generalRequests'),
            fn($qualifications) => $qualifications->filter(fn($q) => !empty($this->fd($q, 'generalRequests', [])))->count()
        );

        // Demandes spécifiques (pondérées)
        $weightedTopSpecificRequests = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            fn($qualifications) => $this->countFromArrayField($qualifications, 'specificRequests'),
            fn($qualifications) => $qualifications->filter(fn($q) => !empty($this->fd($q, 'specificRequests', [])))->count()
        );

        // Autres demandes spécifiques croisées (pondérées)
        $weightedOtherSpecificRequests = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            fn($qualifications) => $this->countFromArrayField($qualifications, 'otherSpecificRequests'),
            fn($qualifications) => $qualifications->filter(fn($q) => !empty($this->fd($q, 'otherSpecificRequests', [])))->count()
        );

        // Demandes spécifiques par ville (NON pondérées, pour affichage détaillé)
        $specificRequestsByCity = [];
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->filterByCity($cityKey);
            if (!$qualifications->isEmpty()) {
                $citySpecificRequests = $this->countFromArrayField($qualifications, 'specificRequests')
                    ->sortDesc();
                $specificRequestsByCity[$cityKey] = $citySpecificRequests->toArray();
            }
        }

        // Textes libres (toutes les demandes, non pondérées)
        $allOtherRequests = [];
        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->filterByCity($cityKey);
            $otherRequests = $qualifications
                ->map(fn($q) => $this->fd($q, 'otherRequest'))
                ->filter()
                ->values();
            $allOtherRequests = array_merge($allOtherRequests, $otherRequests->toArray());
        }

        return [
            'generalRequests' => $this->getTopN($weightedGeneralRequests),
            'specificRequests' => $specificRequestsByCity,
            'topSpecificRequests' => $this->getTopN($weightedTopSpecificRequests),
            'otherSpecificRequests' => $this->getTopN($weightedOtherSpecificRequests),
            'otherRequests' => $allOtherRequests,
        ];
    }

    /**
     * Get contact data statistics AVEC PONDÉRATION PAR VILLE
     *
     * Calcule les statistiques de contact :
     * - Nombre et taux d'emails fournis (pondéré)
     * - Nombre et taux d'acceptation newsletter (pondéré)
     * - Méthodes de contact préférées (pondéré)
     * Chaque ville a le même poids dans le calcul des taux
     *
     * @param array $cities Villes à analyser (vide = toutes)
     * @param mixed $startDate Date de début
     * @param mixed $endDate Date de fin
     * @param string $status Statut du filtre
     * @return array ['emailProvided', 'emailRate', 'newsletterAccepted', 'newsletterRate', 'contactMethods']
     */
    public function getContactStats(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        // Charger les données une fois
        $this->loadQualificationsOnce($cities, $startDate, $endDate, $status);

        $allCities = Qualification::getCities();
        $citiesToAnalyze = !empty($cities) ? $cities : array_keys($allCities);

        // Méthodes de contact (pondérées)
        $weightedContactMethods = $this->calculateCityWeightedStats(
            $citiesToAnalyze,
            function($qualifications) {
                return $qualifications
                    ->map(fn($q) => $this->fd($q, 'contactMethod'))
                    ->filter()
                    ->countBy();
            }
        );

        // Calculer les taux email et newsletter (pondérés par ville)
        $totalEmailRate = 0;
        $totalNewsletterRate = 0;
        $totalEmailProvided = 0;
        $totalNewsletterAccepted = 0;

        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->filterByCity($cityKey);

            if ($qualifications->isEmpty()) {
                continue;
            }

            $cityTotal = $qualifications->count();

            // Emails fournis
            $emailProvided = $qualifications->filter(fn($q) => $this->hasEmail($q))->count();
            $totalEmailProvided += $emailProvided;
            $emailRate = $cityTotal > 0 ? ($emailProvided / $cityTotal) * 100 : 0;
            $totalEmailRate += $emailRate;

            // Newsletter
            $newsletterAccepted = $qualifications->filter(fn($q) => $this->hasNewsletterConsent($q))->count();
            $totalNewsletterAccepted += $newsletterAccepted;
            $newsletterRate = $emailProvided > 0 ? ($newsletterAccepted / $emailProvided) * 100 : 0;
            $totalNewsletterRate += $newsletterRate;
        }

        // Normaliser les taux par le nombre de villes
        $nbCities = count($citiesToAnalyze);
        $avgEmailRate = $nbCities > 0 ? round($totalEmailRate / $nbCities, 1) : 0;
        $avgNewsletterRate = $nbCities > 0 ? round($totalNewsletterRate / $nbCities, 1) : 0;

        return [
            'emailProvided' => $totalEmailProvided,
            'emailRate' => $avgEmailRate,
            'newsletterAccepted' => $totalNewsletterAccepted,
            'newsletterRate' => $avgNewsletterRate,
            'contactMethods' => $weightedContactMethods,
        ];
    }

    // ============================================================
    // HELPER METHODS - Data Loading & Caching
    // ============================================================

    /**
     * Load qualifications once and cache them
     * Évite de recharger plusieurs fois les mêmes données de la DB
     * Multi-key cache: peut stocker plusieurs jeux de données simultanément
     *
     * @param array $cities Cities to filter
     * @param mixed $startDate Start date filter
     * @param mixed $endDate End date filter
     * @param string $status Status filter ('all', 'completed', 'incomplete')
     * @return \Illuminate\Support\Collection Cached collection of qualifications
     */
    protected function loadQualificationsOnce(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
    {
        $cacheKey = md5(json_encode([$cities, $startDate, $endDate, $status]));

        // Check if this specific dataset is already cached
        if (isset($this->cachedQualifications[$cacheKey])) {
            $this->currentCacheKey = $cacheKey;
            return $this->cachedQualifications[$cacheKey];
        }

        // Load from database and cache
        $this->cachedQualifications[$cacheKey] = $this->baseQuery($cities, $startDate, $endDate, $status)->get();
        $this->currentCacheKey = $cacheKey;

        return $this->cachedQualifications[$cacheKey];
    }

    /**
     * Filter cached qualifications by city
     * Uses the currently active cache key
     * Always returns a fresh collection with reset keys
     *
     * @param string $cityKey City key to filter
     * @return \Illuminate\Support\Collection
     */
    protected function filterByCity($cityKey)
    {
        if ($this->currentCacheKey === null || !isset($this->cachedQualifications[$this->currentCacheKey])) {
            return collect([]);
        }

        return $this->cachedQualifications[$this->currentCacheKey]
            ->where('city', $cityKey)
            ->values(); // Reset array keys for clean iteration
    }

    // ============================================================
    // HELPER METHODS - Data Extraction
    // ============================================================

    /**
     * Safe form_data accessor
     * Utilise data_get de Laravel pour un accès sécurisé aux données JSON
     *
     * @param Qualification $qualification
     * @param string $key Key to extract (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function fd($qualification, $key, $default = null)
    {
        return data_get($qualification->form_data, $key, $default);
    }

    /**
     * Count occurrences from array field in form_data
     * Helper générique pour compter les éléments dans les champs tableau
     *
     * @param \Illuminate\Support\Collection $qualifications
     * @param string $field Field name in form_data
     * @return \Illuminate\Support\Collection CountBy collection
     */
    protected function countFromArrayField($qualifications, $field)
    {
        return $qualifications
            ->flatMap(fn($q) => $this->fd($q, $field, []))
            ->filter()
            ->countBy();
    }

    /**
     * Extract country from qualification
     * Gère la logique 'Autre' + 'otherCountry'
     *
     * @param Qualification $qualification
     * @return string|null Country name or null
     */
    protected function extractCountry($qualification)
    {
        $country = $this->fd($qualification, 'country');
        if ($country === 'Autre' && $this->fd($qualification, 'otherCountry')) {
            return $this->fd($qualification, 'otherCountry');
        }
        return $country;
    }

    /**
     * Extract departments from qualification
     * Retourne un tableau vide si departmentUnknown est true
     *
     * @param Qualification $qualification
     * @return array Array of department codes
     */
    protected function extractDepartments($qualification)
    {
        if ($this->fd($qualification, 'departmentUnknown', false)) {
            return [];
        }
        return $this->fd($qualification, 'departments', []);
    }

    /**
     * Check if qualification is from France
     *
     * @param Qualification $qualification
     * @return bool
     */
    protected function isFrance($qualification)
    {
        return $this->fd($qualification, 'country') === 'France';
    }

    /**
     * Check if qualification has email
     *
     * @param Qualification $qualification
     * @return bool
     */
    protected function hasEmail($qualification)
    {
        return !empty($this->fd($qualification, 'email'));
    }

    /**
     * Check if qualification has newsletter consent
     *
     * @param Qualification $qualification
     * @return bool
     */
    protected function hasNewsletterConsent($qualification)
    {
        return $this->hasEmail($qualification) && $this->fd($qualification, 'consentNewsletter', false);
    }

    // ============================================================
    // HELPER METHODS - Weighting & Normalization
    // ============================================================

    /**
     * Add weighted value to accumulator
     * Méthode standard de pondération utilisée partout
     *
     * @param array &$store Accumulator array (passed by reference)
     * @param string $key Key to add/update
     * @param int $count Count in this city
     * @param int $cityTotal Total items in this city
     * @return void
     */
    protected function addWeighted(&$store, $key, $count, $cityTotal)
    {
        if ($cityTotal <= 0) {
            return;
        }

        $percentage = ($count / $cityTotal) * 100;
        $store[$key] = ($store[$key] ?? 0) + $percentage;
    }

    /**
     * Normalize weighted array by number of cities
     * Divise chaque valeur par le nombre de villes pour obtenir la moyenne
     *
     * @param array $array Array to normalize
     * @param int $nbCities Number of cities
     * @param int $precision Decimal precision (default: 2)
     * @return array Normalized array
     */
    protected function normalizeByCity($array, $nbCities, $precision = 2)
    {
        if ($nbCities <= 0) {
            return $array;
        }

        $normalized = [];
        foreach ($array as $key => $value) {
            $normalized[$key] = round($value / $nbCities, $precision);
        }

        return $normalized;
    }

    /**
     * Get top N items from array
     * Trie par valeur décroissante et retourne les N premiers
     *
     * @param array $array Array to extract from
     * @param int $n Number of items to return (default: TOP_N_LIMIT)
     * @return array Top N items
     */
    protected function getTopN($array, $n = null)
    {
        $n = $n ?? self::TOP_N_LIMIT;
        arsort($array);
        return array_slice($array, 0, $n, true);
    }

    /**
     * Calculate city-weighted statistics
     * Méthode universelle pour calculer des stats pondérées par ville
     * Protection contre division par zéro intégrée
     *
     * @param array $citiesToAnalyze Cities to analyze
     * @param callable $extractor Function to extract data from qualifications (receives Collection, returns countBy array)
     * @param callable|null $denominator Function to calculate denominator (receives Collection, returns int). If null, uses collection count
     * @return array Weighted statistics
     */
    protected function calculateCityWeightedStats($citiesToAnalyze, $extractor, $denominator = null)
    {
        $weighted = [];
        $validCities = 0;

        foreach ($citiesToAnalyze as $cityKey) {
            $qualifications = $this->filterByCity($cityKey);

            if ($qualifications->isEmpty()) {
                continue;
            }

            // Calculer le dénominateur et protéger contre division par zéro
            $cityTotal = $denominator ? $denominator($qualifications) : $qualifications->count();
            $cityTotal = max(1, $cityTotal); // Protection: minimum 1

            if ($cityTotal <= 0) {
                continue;
            }

            $cityCounts = $extractor($qualifications);

            foreach ($cityCounts as $key => $count) {
                $this->addWeighted($weighted, $key, $count, $cityTotal);
            }

            $validCities++;
        }

        // Normaliser par le nombre de villes qui ont effectivement des données
        return $this->normalizeByCity($weighted, max(1, $validCities));
    }

    // ============================================================
    // DATABASE COMPATIBILITY HELPERS
    // ============================================================

    /**
     * Get date format SQL for temporal grouping
     * Compatible avec SQLite et MySQL/MariaDB
     *
     * @param string $groupBy Groupement : 'hour', 'day', 'week', 'month', 'year'
     * @return string SQL expression for date formatting
     */
    protected function getDateFormatSql($groupBy)
    {
        $driver = DB::connection()->getDriverName();

        // Définir les formats selon le type de groupement
        $formats = [
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => $driver === 'sqlite' ? '%Y-%W' : '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];

        $dateFormat = $formats[$groupBy] ?? '%Y-%m-%d';

        // Retourner la fonction SQL appropriée selon le driver
        return $driver === 'sqlite'
            ? "strftime('{$dateFormat}', created_at)"
            : "DATE_FORMAT(created_at, '{$dateFormat}')";
    }

    // ============================================================
    // BASE QUERY
    // ============================================================

    /**
     * Base query with filters
     *
     * Construit une requête Eloquent de base avec les filtres communs :
     * - Villes (whereIn)
     * - Dates de début et fin
     * - Statut de complétion
     *
     * @param array $cities Villes à filtrer (vide = toutes)
     * @param mixed $startDate Date de début (null = pas de limite)
     * @param mixed $endDate Date de fin (null = pas de limite)
     * @param string $status Statut : 'all', 'completed', 'incomplete'
     * @return \Illuminate\Database\Eloquent\Builder Query builder
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
