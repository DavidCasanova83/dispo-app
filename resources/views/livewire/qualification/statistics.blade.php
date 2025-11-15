<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Statistiques des Qualifications
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Analyse complète des données de qualification de l'Oti Verdon Tourisme.
            </p>
        </div>

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtres</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Villes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Villes
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="selectedCities" value="all"
                                @if (count($selectedCities) === count($cities)) checked @endif
                                class="rounded border-gray-300 dark:border-gray-600 text-[#3E9B90] focus:ring-[#3E9B90]"
                                x-on:change="if($el.checked) { $wire.set('selectedCities', @js(array_keys($cities))) } else { $wire.set('selectedCities', []) }">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Toutes</span>
                        </label>
                        @foreach ($cities as $cityKey => $cityName)
                            <label class="flex items-center">
                                <input type="checkbox" wire:model.live="selectedCities" value="{{ $cityKey }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-[#3E9B90] focus:ring-[#3E9B90]">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $cityName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Période prédéfinie -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Période
                    </label>
                    <select wire:model.live="dateRange"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <option value="7d">7 derniers jours</option>
                        <option value="30d">30 derniers jours</option>
                        <option value="3m">3 derniers mois</option>
                        <option value="6m">6 derniers mois</option>
                        <option value="1y">1 an</option>
                        <option value="all">Tout</option>
                        <option value="custom">Personnalisé</option>
                    </select>
                </div>

                <!-- Dates personnalisées -->
                @if ($dateRange === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date de début
                        </label>
                        <input type="date" wire:model.live="startDate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date de fin
                        </label>
                        <input type="date" wire:model.live="endDate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                    </div>
                @endif

                <!-- Statut -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Statut
                    </label>
                    <select wire:model.live="status"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                        <option value="all">Toutes</option>
                        <option value="completed">Complétées</option>
                        <option value="incomplete">Brouillons</option>
                    </select>
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
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
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
                            Il n'y a actuellement aucune qualification correspondant aux filtres sélectionnés. Essayez
                            de modifier les filtres ou d'ajouter des données via le formulaire.
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
                    <div id="temporalChart"></div>
                </div>

                <!-- Row: Comparatif villes + Provenance -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Comparatif par ville -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Comparatif par ville</h3>
                        <div id="cityComparisonChart"></div>
                    </div>

                    <!-- Provenance géographique -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pays de provenance</h3>
                        <div id="countriesChart"></div>
                    </div>
                </div>

                <!-- Row: Profils + Âges -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Profils visiteurs -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Profils visiteurs</h3>
                        <div id="profilesChart"></div>
                    </div>

                    <!-- Tranches d'âge -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tranches d'âge</h3>
                        <div id="ageGroupsChart"></div>
                    </div>
                </div>

                <!-- Demandes générales -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top 10 des demandes générales
                    </h3>
                    <div id="generalRequestsChart"></div>
                </div>

                <!-- Demandes spécifiques par ville -->
                @if (count($selectedCities) > 0 && count($selectedCities) <= 3)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Demandes spécifiques par
                            ville</h3>
                        <div class="grid grid-cols-1 gap-6">
                            @foreach ($selectedCities as $cityKey)
                                @if (isset($statistics['demands']['specificRequests'][$cityKey]) &&
                                        count($statistics['demands']['specificRequests'][$cityKey]) > 0)
                                    <div>
                                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            {{ $cities[$cityKey] }}</h4>
                                        <div id="specificRequests_{{ $cityKey }}"></div>
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
                        <div id="contactMethodsChart"></div>
                    </div>

                    <!-- Stats email -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Emails fournis</h3>
                        <div class="flex items-center justify-center h-full">
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
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @if ($statistics['kpis']['total'] > 0)
            <script>
                // Attendre que ApexCharts soit chargé
                function initCharts() {
                    if (typeof ApexCharts === 'undefined') {
                        setTimeout(initCharts, 100);
                        return;
                    }

                    console.log('ApexCharts loaded, initializing charts...');

                    // Vérifier que les éléments existent
                    const temporalEl = document.querySelector("#temporalChart");
                    if (!temporalEl) {
                        console.error('Temporal chart element not found');
                        return;
                    }
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#E5E7EB' : '#1F2937';
                    const gridColor = isDark ? '#374151' : '#E5E7EB';

                    // Thème par défaut
                    const chartTheme = {
                        mode: isDark ? 'dark' : 'light',
                        palette: 'palette1',
                    };

                    // 1. Évolution temporelle
                    const temporalData = @json($statistics['temporalEvolution']);
                    console.log('Temporal data:', temporalData);

                    let temporalSeries = [];

                    if (temporalData.global) {
                        temporalSeries = [{
                            name: 'Qualifications',
                            data: temporalData.global.map(item => ({
                                x: item.period,
                                y: parseInt(item.count)
                            }))
                        }];
                    } else {
                        @foreach ($cities as $cityKey => $cityName)
                            @if (in_array($cityKey, $selectedCities))
                                if (temporalData['{{ $cityKey }}'] && temporalData['{{ $cityKey }}'].length > 0) {
                                    temporalSeries.push({
                                        name: '{{ $cityName }}',
                                        data: temporalData['{{ $cityKey }}'].map(item => ({
                                            x: item.period,
                                            y: parseInt(item.count)
                                        }))
                                    });
                                }
                            @endif
                        @endforeach
                    }

                    console.log('Temporal series:', temporalSeries);

                    const temporalChart = new ApexCharts(document.querySelector("#temporalChart"), {
                        series: temporalSeries,
                        chart: {
                            type: 'area',
                            height: 350,
                            toolbar: {
                                show: true
                            },
                            theme: chartTheme
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        xaxis: {
                            type: 'datetime',
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        grid: {
                            borderColor: gridColor
                        },
                        colors: ['#3E9B90', '#F59E0B', '#EF4444', '#8B5CF6', '#10B981'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.4,
                                opacityTo: 0.1,
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            x: {
                                format: 'dd MMM yyyy'
                            }
                        },
                        legend: {
                            labels: {
                                colors: textColor
                            }
                        }
                    });
                    temporalChart.render();

                    // 2. Comparatif par ville
                    const cityStats = @json($statistics['cityStats']);
                    const cityLabels = Object.values(cityStats).map(s => s.name);
                    const cityTotals = Object.values(cityStats).map(s => s.total);
                    const cityCompleted = Object.values(cityStats).map(s => s.completed);

                    const cityComparisonChart = new ApexCharts(document.querySelector("#cityComparisonChart"), {
                        series: [{
                                name: 'Total',
                                data: cityTotals
                            },
                            {
                                name: 'Complétées',
                                data: cityCompleted
                            }
                        ],
                        chart: {
                            type: 'bar',
                            height: 350,
                            theme: chartTheme
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '55%',
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            offsetY: -20,
                            style: {
                                colors: [textColor]
                            }
                        },
                        xaxis: {
                            categories: cityLabels,
                            labels: {
                                style: {
                                    colors: textColor
                                },
                                rotate: -45,
                                rotateAlways: true
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        grid: {
                            borderColor: gridColor
                        },
                        colors: ['#3E9B90', '#10B981'],
                        legend: {
                            labels: {
                                colors: textColor
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
                        }
                    });
                    cityComparisonChart.render();

                    // 3. Pays de provenance
                    const countries = @json($statistics['geographic']['countries']);
                    const countryLabels = Object.keys(countries);
                    const countryValues = Object.values(countries);

                    const countriesChart = new ApexCharts(document.querySelector("#countriesChart"), {
                        series: countryValues,
                        chart: {
                            type: 'donut',
                            height: 350,
                            theme: chartTheme
                        },
                        labels: countryLabels,
                        colors: ['#3E9B90', '#F59E0B', '#EF4444', '#8B5CF6', '#10B981', '#06B6D4', '#EC4899', '#F97316',
                            '#6366F1', '#14B8A6'
                        ],
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: textColor
                            }
                        },
                        dataLabels: {
                            style: {
                                colors: ['#fff']
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
                        }
                    });
                    countriesChart.render();

                    // 4. Profils visiteurs
                    const profiles = @json($statistics['profiles']['profiles']);
                    const profileLabels = Object.keys(profiles);
                    const profileValues = Object.values(profiles);

                    const profilesChart = new ApexCharts(document.querySelector("#profilesChart"), {
                        series: profileValues,
                        chart: {
                            type: 'pie',
                            height: 350,
                            theme: chartTheme
                        },
                        labels: profileLabels,
                        colors: ['#3E9B90', '#F59E0B', '#EF4444', '#8B5CF6', '#10B981'],
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: textColor
                            }
                        },
                        dataLabels: {
                            style: {
                                colors: ['#fff']
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
                        }
                    });
                    profilesChart.render();

                    // 5. Tranches d'âge
                    const ageGroups = @json($statistics['profiles']['ageGroups']);
                    const ageLabels = Object.keys(ageGroups);
                    const ageValues = Object.values(ageGroups);

                    const ageGroupsChart = new ApexCharts(document.querySelector("#ageGroupsChart"), {
                        series: [{
                            name: 'Visiteurs',
                            data: ageValues
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            theme: chartTheme
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            offsetX: 30,
                            style: {
                                colors: [textColor]
                            }
                        },
                        xaxis: {
                            categories: ageLabels,
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        grid: {
                            borderColor: gridColor
                        },
                        colors: ['#3E9B90'],
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
                        }
                    });
                    ageGroupsChart.render();

                    // 6. Demandes générales
                    const generalRequests = @json($statistics['demands']['generalRequests']);
                    const generalLabels = Object.keys(generalRequests);
                    const generalValues = Object.values(generalRequests);

                    const generalRequestsChart = new ApexCharts(document.querySelector("#generalRequestsChart"), {
                        series: [{
                            name: 'Demandes',
                            data: generalValues
                        }],
                        chart: {
                            type: 'bar',
                            height: 400,
                            theme: chartTheme
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            offsetX: 30,
                            style: {
                                colors: [textColor]
                            }
                        },
                        xaxis: {
                            categories: generalLabels,
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: textColor
                                }
                            }
                        },
                        grid: {
                            borderColor: gridColor
                        },
                        colors: ['#3E9B90'],
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
                        }
                    });
                    generalRequestsChart.render();

                    // 7. Demandes spécifiques par ville
                    @foreach ($selectedCities as $cityKey)
                        @if (isset($statistics['demands']['specificRequests'][$cityKey]) &&
                                count($statistics['demands']['specificRequests'][$cityKey]) > 0)
                            const specificRequests_{{ str_replace('-', '_', $cityKey) }} = @json($statistics['demands']['specificRequests'][$cityKey]);
                            const specificLabels_{{ str_replace('-', '_', $cityKey) }} = Object.keys(
                                specificRequests_{{ str_replace('-', '_', $cityKey) }});
                            const specificValues_{{ str_replace('-', '_', $cityKey) }} = Object.values(
                                specificRequests_{{ str_replace('-', '_', $cityKey) }});

                            const specificChart_{{ str_replace('-', '_', $cityKey) }} = new ApexCharts(document.querySelector(
                                "#specificRequests_{{ $cityKey }}"), {
                                series: [{
                                    name: 'Demandes',
                                    data: specificValues_{{ str_replace('-', '_', $cityKey) }}
                                }],
                                chart: {
                                    type: 'bar',
                                    height: 250,
                                    theme: chartTheme
                                },
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        dataLabels: {
                                            position: 'top'
                                        }
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    offsetX: 30,
                                    style: {
                                        colors: [textColor]
                                    }
                                },
                                xaxis: {
                                    categories: specificLabels_{{ str_replace('-', '_', $cityKey) }},
                                    labels: {
                                        style: {
                                            colors: textColor
                                        }
                                    }
                                },
                                yaxis: {
                                    labels: {
                                        style: {
                                            colors: textColor
                                        }
                                    }
                                },
                                grid: {
                                    borderColor: gridColor
                                },
                                colors: ['#F59E0B'],
                                tooltip: {
                                    theme: isDark ? 'dark' : 'light'
                                }
                            });
                            specificChart_{{ str_replace('-', '_', $cityKey) }}.render();
                        @endif
                    @endforeach

                    // 8. Méthodes de contact
                    const contactMethods = @json($statistics['contact']['contactMethods']);
                    console.log('Contact methods data:', contactMethods);
                    const contactLabels = Object.keys(contactMethods);
                    const contactValues = Object.values(contactMethods);
                    console.log('Contact labels:', contactLabels);
                    console.log('Contact values:', contactValues);

                    if (contactValues.length > 0) {
                        const contactMethodsChart = new ApexCharts(document.querySelector("#contactMethodsChart"), {
                            series: contactValues,
                            chart: {
                                type: 'donut',
                                height: 350,
                                theme: chartTheme
                            },
                            labels: contactLabels,
                            colors: ['#3E9B90', '#F59E0B', '#EF4444'],
                            legend: {
                                position: 'bottom',
                                labels: {
                                    colors: textColor
                                }
                            },
                            dataLabels: {
                                style: {
                                    colors: ['#fff']
                                }
                            },
                            tooltip: {
                                theme: isDark ? 'dark' : 'light'
                            }
                        });
                        contactMethodsChart.render();
                        console.log('Contact methods chart rendered');
                    } else {
                        console.warn('No contact methods data available');
                        document.querySelector("#contactMethodsChart").innerHTML =
                            '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Aucune donnée disponible</p>';
                    }

                    console.log('All charts initialized successfully');
                }

                // Initialiser les graphiques quand le DOM est prêt
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initCharts);
                } else {
                    initCharts();
                }

                // Recharger les graphiques quand Livewire met à jour
                document.addEventListener('livewire:update', function() {
                    location.reload();
                });
            </script>
        @endif
    @endpush
</div>
