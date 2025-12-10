<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Statistiques des Brochures
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Analyse des clics sur les brochures - {{ $statistics['periodLabel'] }}
                </p>
            </div>
            <a href="{{ route('admin.images') }}" wire:navigate
                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour aux brochures
            </a>
        </div>

        {{-- Loading Overlay --}}
        <div wire:loading wire:target="applyFilter, setButtonType"
            class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm">
            <div class="rounded-lg shadow-2xl p-8 flex flex-col items-center space-y-4">
                <svg class="animate-spin h-16 w-16 text-[#3E9B90]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">Chargement...</p>
            </div>
        </div>

        {{-- Filtres de période --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtrer par période</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach([
                    '7days' => '7 jours',
                    '30days' => '30 jours',
                    '90days' => '90 jours',
                    '6months' => '6 mois',
                    '1year' => '1 an',
                    'all' => 'Tout'
                ] as $value => $label)
                    <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $periodFilter === $value ? 'bg-[#3E9B90]/10 ring-2 ring-[#3E9B90]' : '' }}">
                        <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="{{ $value }}"
                            class="w-4 h-4 text-[#3E9B90] border-gray-300 focus:ring-[#3E9B90] dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Filtre par type de bouton --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtrer par type de bouton</h3>
            <div class="flex flex-wrap gap-3">
                <button wire:click="setButtonType(null)"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ !$buttonTypeFilter ? 'bg-[#3E9B90] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    Tous
                </button>
                @foreach($buttonTypes as $type => $label)
                    <button wire:click="setButtonType('{{ $type }}')"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $buttonTypeFilter === $type ? 'bg-[#3E9B90] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {{-- Total Clics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-[#3E9B90]/10 rounded-lg">
                        <svg class="w-6 h-6 text-[#3E9B90]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Clics</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['totalClicks']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Consulter --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Consulter</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['clicksByType']['consulter'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            {{-- Télécharger --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Télécharger</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['clicksByType']['telecharger'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            {{-- Copier Lien --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Copier Lien</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['clicksByType']['copier_lien'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Classement des brochures --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Classement des brochures les plus consultées
                @if($buttonTypeFilter)
                    <span class="text-sm font-normal text-gray-500">({{ $buttonTypes[$buttonTypeFilter] }})</span>
                @endif
            </h3>

            @if($statistics['brochureRanking']->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune donnée</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aucun clic enregistré pour cette période.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Brochure</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Clics</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($statistics['brochureRanking'] as $index => $brochure)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($index < 3)
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index === 0 ? 'bg-yellow-100 text-yellow-800' : ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800') }} font-bold text-sm">
                                                {{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400 pl-2">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <img class="h-12 w-12 rounded-lg object-cover shadow-sm"
                                                    src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                                    alt="{{ $brochure->title ?? $brochure->name }}">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $brochure->title ?? $brochure->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-[#3E9B90]/10 text-[#3E9B90]">
                                            {{ number_format($brochure->total_clicks) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Statistiques utilisateurs --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Répartition des visiteurs</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Visiteurs connectés</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['authenticatedClicks']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $authPercent = $statistics['totalClicks'] > 0 ? ($statistics['authenticatedClicks'] / $statistics['totalClicks']) * 100 : 0;
                        @endphp
                        <div class="bg-[#3E9B90] h-2 rounded-full" style="width: {{ $authPercent }}%"></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Visiteurs anonymes</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['anonymousClicks']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $anonPercent = $statistics['totalClicks'] > 0 ? ($statistics['anonymousClicks'] / $statistics['totalClicks']) * 100 : 0;
                        @endphp
                        <div class="bg-gray-400 h-2 rounded-full" style="width: {{ $anonPercent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Répartition par action</h3>
                <div class="space-y-4">
                    @php
                        $total = $statistics['totalClicks'] ?: 1;
                        $consulter = $statistics['clicksByType']['consulter'] ?? 0;
                        $telecharger = $statistics['clicksByType']['telecharger'] ?? 0;
                        $copier = $statistics['clicksByType']['copier_lien'] ?? 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-blue-600 dark:text-blue-400">Consulter</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($consulter / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($consulter / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-red-600 dark:text-red-400">Télécharger</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($telecharger / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ ($telecharger / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-green-600 dark:text-green-400">Copier Lien</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($copier / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($copier / $total) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
