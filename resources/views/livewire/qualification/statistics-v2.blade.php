<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Statistiques des Qualifications V2
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Analyse complète des données de qualification de l'Oti Verdon Tourisme (Chart.js).
                </p>
            </div>
            <button x-data x-on:click="$dispatch('open-modal', 'export-modal')"
                class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg shadow-md transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Exporter les données
            </button>
        </div>

        <!-- Loading Overlay -->
        <div wire:loading wire:target="applyFilter"
            class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
            <div class="rounded-lg shadow-2xl p-8 flex flex-col items-center space-y-4">
                <svg class="animate-spin h-16 w-16 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">Veuillez patienter</p>
            </div>
        </div>

        <!-- Filtres de période -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtrer par période</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <label
                    class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="7days"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        7 derniers jours
                    </span>
                </label>
                <label
                    class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="30days"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        30 derniers jours
                    </span>
                </label>
                <label
                    class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="90days"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        90 derniers jours
                    </span>
                </label>
                <label
                    class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="180days"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        180 derniers jours
                    </span>
                </label>
                <label
                    class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="all"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Toutes les données
                    </span>
                </label>
            </div>
        </div>

        <!-- Modal Export -->
        <div x-data="{ open: false }" x-on:open-modal.window="if ($event.detail === 'export-modal') open = true"
            x-on:close-modal.window="if ($event.detail === 'export-modal') open = false"
            x-on:keydown.escape.window="open = false" x-show="open" class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" x-on:click="open = false"></div>

            <!-- Modal Content -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
                    x-on:click.stop>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Exporter toutes les données</h3>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        L'export inclura toutes les qualifications enregistrées dans le système, sans filtrage.
                    </p>

                    <form action="{{ route('qualification.export') }}" method="GET" target="_blank">
                        <!-- Paramètres cachés pour exporter toutes les données -->
                        <input type="hidden" name="dateRange" value="all">
                        <input type="hidden" name="status" value="all">

                        <!-- Buttons -->
                        <div class="flex gap-3">
                            <button type="button" x-on:click="open = false"
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Annuler
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Télécharger Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Qualifications</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($statistics['kpis']['total']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Taux de complétion -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Taux de complétion</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ $statistics['kpis']['completionRate'] }}%
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Cette semaine -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cette semaine</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($statistics['kpis']['thisWeek']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Croissance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Croissance</p>
                        <p
                            class="text-3xl font-bold mt-2 {{ $statistics['kpis']['growth'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $statistics['kpis']['growth'] > 0 ? '+' : '' }}{{ $statistics['kpis']['growth'] }}%
                        </p>
                    </div>
                    <div
                        class="p-3 {{ $statistics['kpis']['growth'] >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }} rounded-full">
                        <svg class="w-8 h-8 {{ $statistics['kpis']['growth'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        @if ($statistics['kpis']['total'] == 0)
            <!-- Message si pas de données -->
            <div
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">Aucune donnée disponible
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                            Il n'y a actuellement aucune qualification enregistrée dans le système.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <!-- Graphiques -->
            <div class="space-y-6">
                <!-- Évolution temporelle -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Évolution temporelle</h3>
                    <div wire:ignore class="h-80">
                        <canvas id="temporalChart"></canvas>
                    </div>
                </div>

                <!-- Row: Comparatif villes + Provenance -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Comparatif par ville -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Qualifications complétées
                            par utilisateur</h3>
                        <div wire:ignore class="h-80">
                            <canvas id="cityComparisonChart"></canvas>
                        </div>
                    </div>

                    <!-- Provenance géographique -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pays de provenance</h3>
                        <div wire:ignore class="h-80">
                            <canvas id="countriesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Row: Profils + Âges -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Profils visiteurs -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Profils visiteurs</h3>
                        <div wire:ignore class="h-80">
                            <canvas id="profilesChart"></canvas>
                        </div>
                    </div>

                    <!-- Tranches d'âge -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tranches d'âge</h3>
                        <div wire:ignore class="h-80">
                            <canvas id="ageGroupsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Demandes générales -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top 10 des demandes générales
                    </h3>
                    <div wire:ignore class="h-96">
                        <canvas id="generalRequestsChart"></canvas>
                    </div>
                </div>

                <!-- Row: Top 10 Demandes spécifiques + Top 10 Départements -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top 10 Demandes spécifiques -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top 10 des demandes
                            spécifiques</h3>
                        <div wire:ignore class="h-96">
                            <canvas id="topSpecificRequestsChart"></canvas>
                        </div>
                    </div>

                    <!-- Top 10 Départements -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top 10 des départements
                        </h3>
                        <div wire:ignore class="h-96">
                            <canvas id="topDepartmentsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Demandes spécifiques par ville -->
                @php
                    $citiesToShow = array_keys($cities);
                @endphp
                @if (count($citiesToShow) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Demandes spécifiques par
                            ville</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach ($citiesToShow as $cityKey)
                                @if (isset($statistics['demands']['specificRequests'][$cityKey]) &&
                                        count($statistics['demands']['specificRequests'][$cityKey]) > 0)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            {{ $cities[$cityKey] }}</h4>
                                        <div wire:ignore class="h-80">
                                            <canvas id="specificRequests_{{ $cityKey }}"></canvas>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Row: Contact stats -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Méthodes de contact -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Méthodes de contact</h3>
                        <div wire:ignore class="h-80">
                            <canvas id="contactMethodsChart"></canvas>
                        </div>
                    </div>

                    <!-- Stats email -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Emails fournis</h3>
                        <div class="flex items-center justify-center h-80">
                            <div class="text-center">
                                <div class="text-6xl font-bold text-[#3E9B90] mb-2">
                                    {{ number_format($statistics['contact']['emailProvided']) }}
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    emails collectés
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @if (count($statistics['demands']['otherRequests']) > 0)
                    <!-- Demandes textuelles -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Demandes textuelles
                            (échantillon)</h3>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach (array_slice($statistics['demands']['otherRequests'], 0, 20) as $request)
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $request }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
        </script>
        @if ($statistics['kpis']['total'] > 0)
            <script>
                // Registre global pour stocker les instances de graphiques
                let chartInstances = {};

                // Fonction pour détruire tous les graphiques existants
                function destroyAllCharts() {
                    Object.keys(chartInstances).forEach(key => {
                        if (chartInstances[key]) {
                            chartInstances[key].destroy();
                        }
                    });
                    chartInstances = {};
                }

                // Attendre que Chart.js soit chargé
                function initCharts(statistics) {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => initCharts(statistics), 100);
                        return;
                    }

                    console.log('Chart.js loaded, initializing charts...', statistics);

                    // Détruire les graphiques existants avant de les recréer
                    destroyAllCharts();

                    // Détecter le mode sombre
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#E5E7EB' : '#1F2937';
                    const gridColor = isDark ? '#374151' : '#E5E7EB';
                    const backgroundColor = isDark ? '#1F2937' : '#FFFFFF';

                    // Palette de couleurs
                    const colors = ['#3E9B90', '#F59E0B', '#EF4444', '#8B5CF6', '#10B981', '#06B6D4', '#EC4899', '#F97316'];

                    // Liste des villes (utilisée par plusieurs graphiques)
                    const cities = @json($cities);

                    // Configuration par défaut pour tous les graphiques
                    Chart.defaults.color = textColor;
                    Chart.defaults.borderColor = gridColor;

                    // 1. Évolution temporelle (Line chart avec fill)
                    const temporalData = statistics.temporalEvolution;
                    console.log('Temporal data:', temporalData);

                    let temporalDatasets = [];
                    if (temporalData.global) {
                        temporalDatasets = [{
                            label: 'Qualifications',
                            data: temporalData.global.map(item => ({
                                x: item.period,
                                y: parseInt(item.count)
                            })),
                            borderColor: colors[0],
                            backgroundColor: colors[0] + '40',
                            fill: true,
                            tension: 0.4
                        }];
                    } else {
                        let colorIndex = 0;
                        Object.entries(cities).forEach(([cityKey, cityName]) => {
                            if (temporalData[cityKey] && temporalData[cityKey].length > 0) {
                                temporalDatasets.push({
                                    label: cityName,
                                    data: temporalData[cityKey].map(item => ({
                                        x: item.period,
                                        y: parseInt(item.count)
                                    })),
                                    borderColor: colors[colorIndex % colors.length],
                                    backgroundColor: colors[colorIndex % colors.length] + '40',
                                    fill: true,
                                    tension: 0.4
                                });
                                colorIndex++;
                            }
                        });
                    }

                    const temporalCtx = document.getElementById('temporalChart');
                    if (temporalCtx) {
                        chartInstances.temporalChart = new Chart(temporalCtx, {
                            type: 'line',
                            data: {
                                datasets: temporalDatasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        labels: {
                                            color: textColor,
                                            padding: 15,
                                            font: {
                                                size: 12
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false
                                    }
                                },
                                scales: {
                                    x: {
                                        type: 'time',
                                        time: {
                                            unit: 'day'
                                        },
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 2. Comparatif par ville (Stacked bar chart)
                    const cityStats = statistics.cityStats;
                    const cityLabels = Object.values(cityStats).map(s => s.name);

                    const allUsers = new Set();
                    Object.values(cityStats).forEach(city => {
                        city.byUser.forEach(user => {
                            allUsers.add(user.user_name);
                        });
                    });

                    const cityDatasets = Array.from(allUsers).map((userName, index) => {
                        return {
                            label: userName,
                            data: Object.values(cityStats).map(city => {
                                const userEntry = city.byUser.find(u => u.user_name === userName);
                                return userEntry ? userEntry.count : 0;
                            }),
                            backgroundColor: colors[index % colors.length]
                        };
                    });

                    const cityCtx = document.getElementById('cityComparisonChart');
                    if (cityCtx) {
                        chartInstances.cityComparisonChart = new Chart(cityCtx, {
                            type: 'bar',
                            data: {
                                labels: cityLabels,
                                datasets: cityDatasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            color: textColor,
                                            padding: 15,
                                            font: {
                                                size: 12
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        stacked: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    y: {
                                        stacked: true,
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 3. Pays de provenance (Doughnut chart)
                    const countries = statistics.geographic.countries;
                    const countryLabels = Object.keys(countries);
                    const countryValues = Object.values(countries);

                    const countriesCtx = document.getElementById('countriesChart');
                    if (countriesCtx) {
                        chartInstances.countriesChart = new Chart(countriesCtx, {
                            type: 'doughnut',
                            data: {
                                labels: countryLabels,
                                datasets: [{
                                    data: countryValues,
                                    backgroundColor: colors
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            color: textColor,
                                            padding: 15,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 4. Profils visiteurs (Pie chart)
                    const profiles = statistics.profiles.profiles;
                    const profileLabels = Object.keys(profiles);
                    const profileValues = Object.values(profiles);

                    const profilesCtx = document.getElementById('profilesChart');
                    if (profilesCtx) {
                        chartInstances.profilesChart = new Chart(profilesCtx, {
                            type: 'pie',
                            data: {
                                labels: profileLabels,
                                datasets: [{
                                    data: profileValues,
                                    backgroundColor: colors
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            color: textColor,
                                            padding: 15,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 5. Tranches d'âge (Horizontal bar chart)
                    const ageGroups = statistics.profiles.ageGroups;
                    const ageLabels = Object.keys(ageGroups);
                    const ageValues = Object.values(ageGroups);

                    const ageGroupsCtx = document.getElementById('ageGroupsChart');
                    if (ageGroupsCtx) {
                        chartInstances.ageGroupsChart = new Chart(ageGroupsCtx, {
                            type: 'bar',
                            data: {
                                labels: ageLabels,
                                datasets: [{
                                    label: 'Visiteurs',
                                    data: ageValues,
                                    backgroundColor: colors[0]
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 6. Demandes générales (Horizontal bar chart)
                    const generalRequests = statistics.demands.generalRequests;
                    const generalLabels = Object.keys(generalRequests);
                    const generalValues = Object.values(generalRequests);

                    const generalRequestsCtx = document.getElementById('generalRequestsChart');
                    if (generalRequestsCtx) {
                        chartInstances.generalRequestsChart = new Chart(generalRequestsCtx, {
                            type: 'bar',
                            data: {
                                labels: generalLabels,
                                datasets: [{
                                    label: 'Demandes',
                                    data: generalValues,
                                    backgroundColor: colors[0]
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: gridColor
                                        },
                                        ticks: {
                                            color: textColor,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 7. Top 10 des demandes spécifiques (Horizontal bar chart)
                    const topSpecificRequests = statistics.demands.topSpecificRequests;
                    const topSpecificLabels = Object.keys(topSpecificRequests);
                    const topSpecificValues = Object.values(topSpecificRequests);

                    const topSpecificCtx = document.getElementById('topSpecificRequestsChart');
                    if (topSpecificCtx) {
                        if (topSpecificValues.length > 0) {
                            chartInstances.topSpecificRequestsChart = new Chart(topSpecificCtx, {
                                type: 'bar',
                                data: {
                                    labels: topSpecificLabels,
                                    datasets: [{
                                        label: 'Demandes',
                                        data: topSpecificValues,
                                        backgroundColor: colors[1]
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            grid: {
                                                color: gridColor
                                            },
                                            ticks: {
                                                color: textColor
                                            }
                                        },
                                        y: {
                                            grid: {
                                                color: gridColor
                                            },
                                            ticks: {
                                                color: textColor,
                                                font: {
                                                    size: 11
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            topSpecificCtx.parentElement.innerHTML =
                                '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée disponible</p>';
                        }
                    }

                    // 8. Top 10 des départements (Horizontal bar chart)
                    const departments = statistics.geographic.departments;
                    const departmentLabels = Object.keys(departments);
                    const departmentValues = Object.values(departments);

                    const departmentsCtx = document.getElementById('topDepartmentsChart');
                    if (departmentsCtx) {
                        if (departmentValues.length > 0) {
                            chartInstances.topDepartmentsChart = new Chart(departmentsCtx, {
                                type: 'bar',
                                data: {
                                    labels: departmentLabels,
                                    datasets: [{
                                        label: 'Qualifications',
                                        data: departmentValues,
                                        backgroundColor: colors[3]
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            grid: {
                                                color: gridColor
                                            },
                                            ticks: {
                                                color: textColor
                                            }
                                        },
                                        y: {
                                            grid: {
                                                color: gridColor
                                            },
                                            ticks: {
                                                color: textColor,
                                                font: {
                                                    size: 11
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            departmentsCtx.parentElement.innerHTML =
                                '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée disponible</p>';
                        }
                    }

                    // 9. Demandes spécifiques par ville (Horizontal bar charts)
                    const citiesToShowJS = Object.keys(cities);
                    const specificRequests = statistics.demands.specificRequests;

                    citiesToShowJS.forEach(cityKey => {
                        if (specificRequests[cityKey] && Object.keys(specificRequests[cityKey]).length > 0) {
                            const citySpecificRequests = specificRequests[cityKey];
                            const specificLabels = Object.keys(citySpecificRequests);
                            const specificValues = Object.values(citySpecificRequests);

                            const specificCtx = document.getElementById(`specificRequests_${cityKey}`);
                            if (specificCtx) {
                                const chartKey = `specificChart_${cityKey.replace(/-/g, '_')}`;
                                chartInstances[chartKey] = new Chart(specificCtx, {
                                    type: 'bar',
                                    data: {
                                        labels: specificLabels,
                                        datasets: [{
                                            label: 'Demandes',
                                            data: specificValues,
                                            backgroundColor: colors[1],
                                            barThickness: 20,
                                            maxBarThickness: 25
                                        }]
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: false
                                            }
                                        },
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                                grid: {
                                                    color: gridColor
                                                },
                                                ticks: {
                                                    color: textColor
                                                }
                                            },
                                            y: {
                                                grid: {
                                                    color: gridColor
                                                },
                                                ticks: {
                                                    color: textColor,
                                                    font: {
                                                        size: 11
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });

                    // 10. Méthodes de contact (Doughnut chart)
                    const contactMethods = statistics.contact.contactMethods;
                    const contactLabels = Object.keys(contactMethods);
                    const contactValues = Object.values(contactMethods);

                    const contactMethodsCtx = document.getElementById('contactMethodsChart');
                    if (contactMethodsCtx) {
                        if (contactValues.length > 0) {
                            chartInstances.contactMethodsChart = new Chart(contactMethodsCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: contactLabels,
                                    datasets: [{
                                        data: contactValues,
                                        backgroundColor: colors.slice(0, 3)
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'bottom',
                                            labels: {
                                                color: textColor,
                                                padding: 15,
                                                font: {
                                                    size: 11
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            contactMethodsCtx.parentElement.innerHTML =
                                '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée disponible</p>';
                        }
                    }

                    console.log('All charts initialized successfully');
                }

                // Initialiser les graphiques quand le DOM est prêt
                const initialStatistics = @json($statistics);
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => initCharts(initialStatistics));
                } else {
                    initCharts(initialStatistics);
                }

                // Écouter l'événement de mise à jour des statistiques
                window.addEventListener('statistics-updated', (event) => {
                    console.log('Statistics updated event received', event.detail);
                    const newStatistics = event.detail.statistics;
                    initCharts(newStatistics);
                });
            </script>
        @endif
    @endpush

</div>
