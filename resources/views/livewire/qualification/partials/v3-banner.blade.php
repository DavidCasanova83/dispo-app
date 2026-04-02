@if ($effectiveMode === 'normalized')
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Mode normalisé actif</p>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                    Chaque bureau pèse de manière égale, quel que soit son volume de saisie.
                    Cela évite qu'un bureau à fort volume ne domine les résultats.
                    Les pourcentages représentent la moyenne des pourcentages de chaque ville.
                </p>
            </div>
        </div>
    </div>
@endif

@if ($isSingleCity)
    <div class="bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-sm font-medium text-teal-800 dark:text-teal-300">
                    Données filtrées pour : <strong>{{ $cities[$selectedCity] ?? $selectedCity }}</strong>
                </span>
            </div>
            <button wire:click="$set('selectedCity', 'all')"
                class="px-3 py-1.5 text-sm rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors">
                Voir toutes les villes
            </button>
        </div>
    </div>
@endif
