<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Statistiques des Qualifications
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Analyse des données de qualification — Oti Verdon Tourisme
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button x-data x-on:click="$dispatch('open-modal', 'v3-export-modal')"
                    class="px-4 py-2 text-sm rounded-lg bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-medium shadow-sm transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter
                </button>
                <a href="{{ route('qualification.index') }}"
                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Retour
                </a>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading wire:target="applyPreset, setMode, toggleMode, setGranularity, exportData"
            class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
            <div class="rounded-lg shadow-2xl p-8 flex flex-col items-center space-y-4">
                <svg class="animate-spin h-16 w-16 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">Mise à jour des statistiques...</p>
            </div>
        </div>

        {{-- Filtres --}}
        @include('livewire.qualification.partials.v3-filters')

        {{-- Bannières --}}
        @include('livewire.qualification.partials.v3-banner')

        {{-- KPIs --}}
        @include('livewire.qualification.partials.v3-kpis')

        @if ($statistics['kpis']['total'] > 0)

            {{-- G1: Évolution temporelle --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Évolution temporelle</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Une courbe par ville + total — Volumes absolus
                        </p>
                    </div>
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        @foreach (['auto' => 'Auto', 'day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois'] as $g => $label)
                            <button wire:click="setGranularity('{{ $g }}')"
                                class="px-3 py-1 text-xs rounded-md transition-colors {{ $granularity === $g ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div wire:ignore class="relative" style="min-height: 350px;">
                    <canvas id="g1-temporal"></canvas>
                </div>
            </div>

            {{-- G2: Répartition par ville --}}
            @if (!$isSingleCity)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Répartition par ville</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Volume de qualifications par bureau</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div wire:ignore class="relative" style="min-height: 300px;">
                            <canvas id="g2-city-bar"></canvas>
                        </div>
                        <div wire:ignore class="relative" style="min-height: 300px;">
                            <canvas id="g2-city-donut"></canvas>
                        </div>
                    </div>
                </div>
            @endif

            {{-- G3: Demandes générales --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Demandes générales</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            @if ($effectiveMode === 'normalized')
                                Pourcentage moyen normalisé par ville
                            @else
                                Nombre total d'occurrences
                            @endif
                            — Un formulaire peut contenir plusieurs demandes
                        </p>
                    </div>
                </div>
                <div wire:ignore class="relative" id="g3-wrapper">
                    <canvas id="g3-general-demands"></canvas>
                </div>
            </div>

            {{-- G4: Profil visiteur --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profil visiteur</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $effectiveMode === 'normalized' ? 'Répartition normalisée par ville' : 'Répartition brute' }}
                            — Catégories sous 3% regroupées dans « Autre »
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div wire:ignore class="relative" style="min-height: 350px;">
                        <canvas id="g4-profiles-donut"></canvas>
                    </div>
                    <div wire:ignore class="relative" style="min-height: 350px;">
                        <canvas id="g4-profiles-bar"></canvas>
                    </div>
                </div>
            </div>

            {{-- G5: Tranches d'âge --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tranches d'âge</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $effectiveMode === 'normalized' ? 'Pourcentage normalisé par ville' : 'Nombre d\'occurrences' }}
                            — Un visiteur peut apparaître dans plusieurs tranches
                        </p>
                    </div>
                </div>
                <div wire:ignore class="relative" style="min-height: 350px;">
                    <canvas id="g5-age-ranges"></canvas>
                </div>
            </div>

            {{-- G6: Origine géographique --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Origine géographique</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            France vs International + Top départements et pays
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div wire:ignore class="relative" style="min-height: 280px;">
                        <canvas id="g6-origin-donut"></canvas>
                    </div>
                    <div wire:ignore class="relative" style="min-height: 280px;">
                        <canvas id="g6-departments"></canvas>
                    </div>
                    <div wire:ignore class="relative" style="min-height: 280px;">
                        <canvas id="g6-countries"></canvas>
                    </div>
                </div>
            </div>

            {{-- G7: Méthode de contact --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Méthode de contact</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $effectiveMode === 'normalized' ? 'Normalisé par ville' : 'Répartition brute' }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div wire:ignore class="relative" style="min-height: 280px;">
                        <canvas id="g7-contact-donut"></canvas>
                    </div>
                    <div wire:ignore class="relative" style="min-height: 280px;">
                        <canvas id="g7-contact-bar"></canvas>
                    </div>
                </div>
            </div>

            {{-- G8: Activité par agent --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activité par agent</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Mode absolu uniquement — Nombre de qualifications saisies par agent
                        </p>
                    </div>
                </div>
                <div wire:ignore class="relative" style="min-height: 300px;">
                    <canvas id="g8-agent-activity"></canvas>
                </div>
            </div>

            {{-- G9: Demandes spécifiques ville (conditionnel) --}}
            @if ($isSingleCity && !empty($statistics['citySpecificDemands']))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Demandes spécifiques — {{ $cities[$selectedCity] ?? $selectedCity }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Demandes propres à ce bureau (mode absolu)
                            </p>
                        </div>
                    </div>
                    <div wire:ignore class="relative" style="min-height: 300px;">
                        <canvas id="g9-city-specific"></canvas>
                    </div>
                </div>
            @endif

            {{-- Synthèses croisées (heatmaps) --}}
            @if (!empty($statistics['crossTabs']))
                <div class="mt-8 mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Synthèses croisées</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Tableaux croisant deux dimensions — l'intensité de la couleur reflète le volume
                    </p>
                </div>

                @if (!empty($statistics['crossTabs']['cityXdemand']))
                    @include('livewire.qualification.partials.v3-heatmap', [
                        'crossTab' => $statistics['crossTabs']['cityXdemand'],
                        'title' => 'Ville x Demande générale',
                        'description' => 'Spécialisation de chaque bureau — Quelles demandes dominent dans chaque ville ?',
                    ])
                @endif

                @if (!empty($statistics['crossTabs']['monthXdemand']))
                    @include('livewire.qualification.partials.v3-heatmap', [
                        'crossTab' => $statistics['crossTabs']['monthXdemand'],
                        'title' => 'Mois x Demande générale',
                        'description' => 'Saisonnalité des demandes — Quelles demandes émergent à quel moment ?',
                    ])
                @endif
            @endif

        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune donnée</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune qualification complétée pour la période et les filtres sélectionnés.</p>
            </div>
        @endif
    </div>

    {{-- Export Modal --}}
    <div x-data="{ show: false, exportStartDate: '{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}', exportEndDate: '{{ $endDate ?? now()->format('Y-m-d') }}', errorMessage: '' }"
        x-on:open-modal.window="if ($event.detail === 'v3-export-modal') show = true"
        x-on:export-error.window="errorMessage = $event.detail.message"
        x-show="show" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50" @click="show = false"></div>
            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Exporter les statistiques</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Export Excel multi-onglets avec les modes absolu et normalisé.
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début</label>
                        <input type="date" x-model="exportStartDate"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin</label>
                        <input type="date" x-model="exportEndDate"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <template x-if="errorMessage">
                        <p class="text-sm text-red-600 dark:text-red-400" x-text="errorMessage"></p>
                    </template>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="show = false"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button @click="errorMessage = ''; $wire.exportData(exportStartDate, exportEndDate); show = false"
                        class="px-4 py-2 text-sm rounded-lg bg-gradient-to-r from-green-600 to-green-700 text-white font-medium hover:from-green-700 hover:to-green-800">
                        Télécharger
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        @if ($statistics['kpis']['total'] > 0)
            <script>
                let v3Charts = {};
                const cityNames = @json($cities);
                const cityColorMap = {
                    'annot': '#3B82F6',
                    'colmars-les-alpes': '#10B981',
                    'entrevaux': '#F59E0B',
                    'la-palud-sur-verdon': '#EF4444',
                    'saint-andre-les-alpes': '#8B5CF6'
                };
                const segmentColors = [
                    '#3E9B90', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
                    '#10B981', '#EC4899', '#F97316', '#06B6D4', '#6366F1',
                    '#84CC16', '#14B8A6', '#E11D48', '#7C3AED', '#D97706',
                    '#059669', '#DB2777', '#EA580C', '#0891B2', '#4F46E5',
                    '#65A30D', '#0D9488'
                ];

                function destroyV3Charts() {
                    Object.keys(v3Charts).forEach(key => {
                        if (v3Charts[key]) v3Charts[key].destroy();
                    });
                    v3Charts = {};
                }

                function noData(ctx, msg = 'Aucune donnée') {
                    if (ctx) ctx.parentElement.innerHTML = `<p class="text-center text-gray-500 dark:text-gray-400 py-8">${msg}</p>`;
                }

                function initV3Charts(statistics) {
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => initV3Charts(statistics), 100);
                        return;
                    }
                    destroyV3Charts();

                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#E5E7EB' : '#1F2937';
                    const gridColor = isDark ? '#374151' : '#E5E7EB';
                    Chart.defaults.color = textColor;
                    Chart.defaults.borderColor = gridColor;

                    const ctx = { isDark, textColor, gridColor };
                    initG1(statistics, ctx);
                    initG2(statistics, ctx);
                    initG3(statistics, ctx);
                    initG4(statistics, ctx);
                    initG5(statistics, ctx);
                    initG6(statistics, ctx);
                    initG7(statistics, ctx);
                    initG8(statistics, ctx);
                    initG9(statistics, ctx);
                }

                // ── G1: Temporal Evolution ──
                function initG1(statistics, ctx) {
                    const el = document.getElementById('g1-temporal');
                    if (!el) return;
                    const d = statistics.temporalEvolution;
                    if (!d || !d.labels || d.labels.length === 0) { noData(el); return; }

                    const datasets = [];
                    // City lines
                    Object.keys(d.datasets).forEach(cityKey => {
                        datasets.push({
                            label: cityNames[cityKey] || cityKey,
                            data: d.datasets[cityKey],
                            borderColor: cityColorMap[cityKey] || '#888',
                            backgroundColor: (cityColorMap[cityKey] || '#888') + '20',
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: d.labels.length > 60 ? 0 : 3,
                            fill: false,
                        });
                    });
                    // Total line
                    datasets.push({
                        label: 'Total',
                        data: d.total,
                        borderColor: '#1F2937',
                        borderWidth: 3,
                        borderDash: [5, 5],
                        tension: 0.3,
                        pointRadius: 0,
                        fill: false,
                    });

                    v3Charts.g1 = new Chart(el, {
                        type: 'line',
                        data: { labels: d.labels, datasets },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } } },
                            scales: {
                                x: { grid: { color: ctx.gridColor }, ticks: { maxTicksLimit: 15, font: { size: 11 } } },
                                y: { grid: { color: ctx.gridColor }, beginAtZero: true }
                            }
                        }
                    });
                }

                // ── G2: City Distribution ──
                function initG2(statistics, ctx) {
                    const barEl = document.getElementById('g2-city-bar');
                    const donutEl = document.getElementById('g2-city-donut');
                    const d = statistics.cityDistribution;
                    if (!d || !d.labels || d.labels.length === 0) { noData(barEl); noData(donutEl); return; }

                    const colors = Object.values(cityColorMap);

                    if (barEl) {
                        v3Charts.g2Bar = new Chart(barEl, {
                            type: 'bar',
                            data: {
                                labels: d.labels,
                                datasets: [{ data: d.values, backgroundColor: colors, borderRadius: 4 }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false } },
                                    y: { grid: { color: ctx.gridColor }, beginAtZero: true }
                                }
                            }
                        });
                    }
                    if (donutEl) {
                        v3Charts.g2Donut = new Chart(donutEl, {
                            type: 'doughnut',
                            data: {
                                labels: d.labels,
                                datasets: [{ data: d.values, backgroundColor: colors, borderWidth: 2, borderColor: ctx.isDark ? '#1F2937' : '#FFF' }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '55%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12 } },
                                    tooltip: {
                                        callbacks: {
                                            label: function(c) {
                                                const total = c.dataset.data.reduce((a, b) => a + b, 0);
                                                const pct = total > 0 ? Math.round((c.parsed / total) * 100) : 0;
                                                return `${c.label}: ${c.parsed} (${pct}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                // ── G3: General Demands ──
                function initG3(statistics, ctx) {
                    const el = document.getElementById('g3-general-demands');
                    const wrapper = document.getElementById('g3-wrapper');
                    if (!el) return;
                    const d = statistics.generalDemands;
                    if (!d || !d.labels || d.labels.length === 0) { noData(el); return; }

                    // Dynamic height: 32px per label, min 200px
                    const dynamicHeight = Math.max(200, d.labels.length * 32 + 40);
                    if (wrapper) wrapper.style.minHeight = dynamicHeight + 'px';

                    const isNorm = d.mode === 'normalized';
                    v3Charts.g3 = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{ data: d.values, backgroundColor: d.labels.map((_, i) => segmentColors[i % segmentColors.length]), borderRadius: 4, barThickness: 18, maxBarThickness: 22 }]
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (c) => isNorm ? `Moyenne normalisée : ${c.parsed.x}%` : `Total : ${c.parsed.x}`,
                                        afterBody: (items) => buildCityTooltip(items[0].label, d, isNorm)
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { color: ctx.gridColor }, ticks: { callback: v => isNorm ? v + '%' : v } },
                                y: { grid: { display: false }, ticks: { font: { size: 11 }, autoSkip: false } }
                            }
                        }
                    });
                }

                // ── G4: Profiles ──
                function initG4(statistics, ctx) {
                    const donutEl = document.getElementById('g4-profiles-donut');
                    const barEl = document.getElementById('g4-profiles-bar');
                    const d = statistics.profiles;
                    if (!d || !d.labels || d.labels.length === 0) { noData(donutEl); noData(barEl); return; }

                    const isNorm = d.mode === 'normalized';
                    const colors = d.labels.map((_, i) => segmentColors[i % segmentColors.length]);

                    if (donutEl) {
                        v3Charts.g4Donut = new Chart(donutEl, {
                            type: 'doughnut',
                            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: colors, borderWidth: 2, borderColor: ctx.isDark ? '#1F2937' : '#FFF' }] },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '60%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } },
                                    tooltip: {
                                        callbacks: {
                                            label: (c) => isNorm ? `${c.label}: ${c.parsed}%` : `${c.label}: ${c.parsed} (${Math.round((c.parsed / c.dataset.data.reduce((a, b) => a + b, 0)) * 100)}%)`,
                                            afterBody: (items) => items[0].label === 'Autre' ? [] : buildCityTooltip(items[0].label, d, isNorm)
                                        }
                                    }
                                }
                            }
                        });
                    }
                    if (barEl) {
                        v3Charts.g4Bar = new Chart(barEl, {
                            type: 'bar',
                            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: colors, borderRadius: 4 }] },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => isNorm ? `${c.parsed.y}%` : `${c.parsed.y}` } } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                                    y: { grid: { color: ctx.gridColor }, ticks: { callback: v => isNorm ? v + '%' : v } }
                                }
                            }
                        });
                    }
                }

                // ── G5: Age Ranges ──
                function initG5(statistics, ctx) {
                    const el = document.getElementById('g5-age-ranges');
                    if (!el) return;
                    const d = statistics.ageRanges;
                    if (!d || !d.labels || d.labels.length === 0) { noData(el); return; }

                    const isNorm = d.mode === 'normalized';
                    v3Charts.g5 = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{ data: d.values, backgroundColor: d.labels.map((_, i) => segmentColors[i % segmentColors.length]), borderRadius: 4 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (c) => isNorm ? `${c.parsed.y}%` : `${c.parsed.y}`,
                                        afterBody: (items) => buildCityTooltip(items[0].label, d, isNorm)
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { grid: { color: ctx.gridColor }, ticks: { callback: v => isNorm ? v + '%' : v }, beginAtZero: true }
                            }
                        }
                    });
                }

                // ── G6: Geographic Origin ──
                function initG6(statistics, ctx) {
                    const donutEl = document.getElementById('g6-origin-donut');
                    const deptEl = document.getElementById('g6-departments');
                    const countryEl = document.getElementById('g6-countries');
                    const d = statistics.geographic;
                    if (!d) { noData(donutEl); noData(deptEl); noData(countryEl); return; }

                    // France/International donut
                    if (donutEl) {
                        v3Charts.g6Donut = new Chart(donutEl, {
                            type: 'doughnut',
                            data: {
                                labels: ['France', 'International'],
                                datasets: [{ data: [d.francePct, d.internationalPct], backgroundColor: ['#3B82F6', '#F59E0B'], borderWidth: 2, borderColor: ctx.isDark ? '#1F2937' : '#FFF' }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '60%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12 } },
                                    tooltip: { callbacks: { label: (c) => `${c.label}: ${c.parsed}%` } }
                                }
                            }
                        });
                    }

                    // Top departments
                    if (deptEl && d.topDepartments && d.topDepartments.labels.length > 0) {
                        v3Charts.g6Depts = new Chart(deptEl, {
                            type: 'bar',
                            data: {
                                labels: d.topDepartments.labels,
                                datasets: [{ data: d.topDepartments.values, backgroundColor: '#3B82F6', borderRadius: 4, barThickness: 20 }]
                            },
                            options: {
                                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, title: { display: true, text: 'Top départements', font: { size: 13 } } },
                                scales: { x: { grid: { color: ctx.gridColor } }, y: { grid: { display: false }, ticks: { font: { size: 10 } } } }
                            }
                        });
                    } else {
                        noData(deptEl, 'Aucun département');
                    }

                    // Top countries
                    if (countryEl && d.topCountries && d.topCountries.labels.length > 0) {
                        v3Charts.g6Countries = new Chart(countryEl, {
                            type: 'bar',
                            data: {
                                labels: d.topCountries.labels,
                                datasets: [{ data: d.topCountries.values, backgroundColor: '#F59E0B', borderRadius: 4, barThickness: 20 }]
                            },
                            options: {
                                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, title: { display: true, text: 'Top pays (hors France)', font: { size: 13 } } },
                                scales: { x: { grid: { color: ctx.gridColor } }, y: { grid: { display: false }, ticks: { font: { size: 10 } } } }
                            }
                        });
                    } else {
                        noData(countryEl, 'Aucun pays international');
                    }
                }

                // ── G7: Contact Methods ──
                function initG7(statistics, ctx) {
                    const donutEl = document.getElementById('g7-contact-donut');
                    const barEl = document.getElementById('g7-contact-bar');
                    const d = statistics.contactMethods;
                    if (!d || !d.labels || d.labels.length === 0) { noData(donutEl); noData(barEl); return; }

                    const isNorm = d.mode === 'normalized';
                    const colors = d.labels.map((_, i) => segmentColors[i % segmentColors.length]);

                    if (donutEl) {
                        v3Charts.g7Donut = new Chart(donutEl, {
                            type: 'doughnut',
                            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: colors, borderWidth: 2, borderColor: ctx.isDark ? '#1F2937' : '#FFF' }] },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '60%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12 } },
                                    tooltip: { callbacks: { label: (c) => isNorm ? `${c.label}: ${c.parsed}%` : `${c.label}: ${c.parsed}` } }
                                }
                            }
                        });
                    }
                    if (barEl) {
                        v3Charts.g7Bar = new Chart(barEl, {
                            type: 'bar',
                            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: colors, borderRadius: 4 }] },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => isNorm ? `${c.parsed.y}%` : `${c.parsed.y}` } } },
                                scales: {
                                    x: { grid: { display: false } },
                                    y: { grid: { color: ctx.gridColor }, ticks: { callback: v => isNorm ? v + '%' : v } }
                                }
                            }
                        });
                    }
                }

                // ── G8: Agent Activity ──
                function initG8(statistics, ctx) {
                    const el = document.getElementById('g8-agent-activity');
                    if (!el) return;
                    const d = statistics.agentActivity;
                    if (!d || !d.labels || d.labels.length === 0) { noData(el); return; }

                    v3Charts.g8 = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{ data: d.values, backgroundColor: d.labels.map((_, i) => segmentColors[i % segmentColors.length]), borderRadius: 4, barThickness: 28 }]
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { color: ctx.gridColor }, beginAtZero: true },
                                y: { grid: { display: false }, ticks: { font: { size: 12 } } }
                            }
                        }
                    });
                }

                // ── G9: City-Specific Demands ──
                function initG9(statistics, ctx) {
                    const el = document.getElementById('g9-city-specific');
                    if (!el) return;
                    const d = statistics.citySpecificDemands;
                    if (!d || !d.labels || d.labels.length === 0) { noData(el); return; }

                    v3Charts.g9 = new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                data: d.values,
                                backgroundColor: d.labels.map((_, i) => segmentColors[i % segmentColors.length]),
                                borderRadius: 4,
                                barThickness: 24
                            }]
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (c) => `${c.parsed.x} occurrence${c.parsed.x > 1 ? 's' : ''}`
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { color: ctx.gridColor }, beginAtZero: true },
                                y: { grid: { display: false }, ticks: { font: { size: 12 } } }
                            }
                        }
                    });
                }

                // ── Shared tooltip builder ──
                function buildCityTooltip(label, data, isNormalized) {
                    const lines = [''];
                    if (isNormalized && data.perCityPct) {
                        lines.push('Détail par ville :');
                        Object.keys(data.perCityPct)
                            .map(c => ({ c, v: data.perCityPct[c]?.[label] ?? 0 }))
                            .sort((a, b) => b.v - a.v)
                            .forEach(e => lines.push(`  ${cityNames[e.c] || e.c}: ${e.v}%`));
                    } else if (data.perCity) {
                        lines.push('Détail par ville :');
                        const entries = Object.keys(data.perCity)
                            .map(c => ({ c, v: data.perCity[c]?.[label] ?? 0 }))
                            .sort((a, b) => b.v - a.v);
                        const total = entries.reduce((s, e) => s + e.v, 0);
                        entries.forEach(e => {
                            const pct = total > 0 ? Math.round((e.v / total) * 100) : 0;
                            lines.push(`  ${cityNames[e.c] || e.c}: ${e.v} (${pct}%)`);
                        });
                    }
                    return lines;
                }

                // Initialize
                const initialV3Statistics = @json($statistics);
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => initV3Charts(initialV3Statistics));
                } else {
                    initV3Charts(initialV3Statistics);
                }

                window.addEventListener('v3-statistics-updated', (event) => {
                    initV3Charts(event.detail.statistics);
                });
            </script>
        @endif
    @endpush
</div>
