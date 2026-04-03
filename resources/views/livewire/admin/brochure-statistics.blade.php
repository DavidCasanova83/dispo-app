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

        {{-- Filtres période + Répartitions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Filtre période --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Période</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach([
                        '7days' => '7j',
                        '30days' => '30j',
                        '90days' => '90j',
                        '6months' => '6 mois',
                        '1year' => '1 an',
                        'all' => 'Tout'
                    ] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="selectedPeriod" wire:change="applyFilter" value="{{ $value }}" class="sr-only peer">
                            <span class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors peer-checked:bg-[#3E9B90] peer-checked:text-white bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Répartition des visiteurs --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Répartition des visiteurs</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Connectés</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['authenticatedClicks']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        @php $authPercent = $statistics['totalClicks'] > 0 ? ($statistics['authenticatedClicks'] / $statistics['totalClicks']) * 100 : 0; @endphp
                        <div class="bg-[#3E9B90] h-1.5 rounded-full" style="width: {{ $authPercent }}%"></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Anonymes</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['anonymousClicks']) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        @php $anonPercent = $statistics['totalClicks'] > 0 ? ($statistics['anonymousClicks'] / $statistics['totalClicks']) * 100 : 0; @endphp
                        <div class="bg-gray-400 h-1.5 rounded-full" style="width: {{ $anonPercent }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Répartition par action --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Répartition par action</h3>
                @php
                    $total = $statistics['totalClicks'] ?: 1;
                    $consulter = $statistics['clicksByType']['consulter'] ?? 0;
                    $telecharger = $statistics['clicksByType']['telecharger'] ?? 0;
                    $copier = $statistics['clicksByType']['copier_lien'] ?? 0;
                @endphp
                <div class="space-y-2">
                    <div>
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-sm text-blue-600 dark:text-blue-400">Consulter</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($consulter / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ ($consulter / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-sm text-red-600 dark:text-red-400">Télécharger</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($telecharger / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ ($telecharger / $total) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-sm text-green-600 dark:text-green-400">Copier</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ round(($copier / $total) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ ($copier / $total) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tableau --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            @if($statistics['brochureRanking']->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune donnée</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucun clic enregistré pour cette période.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Brochure</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider w-28">
                                    <div class="flex items-center justify-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Consulter
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider w-28">
                                    <div class="flex items-center justify-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Télécharger
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider w-28">
                                    <div class="flex items-center justify-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                        </svg>
                                        Copier
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-[#3E9B90] uppercase tracking-wider w-28">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- Ligne totaux --}}
                            <tr class="bg-[#3E9B90]/5 dark:bg-[#3E9B90]/10 font-semibold">
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">Totaux</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        {{ number_format($statistics['clicksByType']['consulter'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                                        {{ number_format($statistics['clicksByType']['telecharger'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                        {{ number_format($statistics['clicksByType']['copier_lien'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#3E9B90]/10 text-[#3E9B90]">
                                        {{ number_format($statistics['totalClicks']) }}
                                    </span>
                                </td>
                            </tr>

                            {{-- Lignes brochures --}}
                            @foreach($statistics['brochureRanking'] as $index => $brochure)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($index < 3)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $index === 0 ? 'bg-yellow-100 text-yellow-800' : ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800') }} font-bold text-xs">
                                                {{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400 pl-2">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-lg object-cover shadow-sm"
                                                    src="{{ $brochure->thumbnail_path ? asset('storage/' . $brochure->thumbnail_path) : asset('storage/' . $brochure->path) }}"
                                                    alt="{{ $brochure->title ?? $brochure->name }}">
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">
                                                    {{ $brochure->title ?? $brochure->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-blue-600 dark:text-blue-400">
                                        {{ number_format($brochure->consulter_clicks) }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-red-600 dark:text-red-400">
                                        {{ number_format($brochure->telecharger_clicks) }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-green-600 dark:text-green-400">
                                        {{ number_format($brochure->copier_lien_clicks) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#3E9B90]/10 text-[#3E9B90]">
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
    </div>
</div>
