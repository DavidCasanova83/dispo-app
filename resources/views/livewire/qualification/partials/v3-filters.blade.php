<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-end gap-6">
        {{-- Raccourcis période --}}
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Période</label>
            <div class="flex flex-wrap gap-2">
                @php
                    $presets = [
                        'this_month' => 'Ce mois',
                        'this_quarter' => 'Ce trimestre',
                        'this_year' => 'Cette année',
                        'last_year_same' => 'N-1 même période',
                        'all' => 'Tout',
                    ];
                @endphp
                @foreach ($presets as $key => $label)
                    <button wire:click="applyPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-sm rounded-lg border transition-colors
                            {{ $periodPreset === $key
                                ? 'bg-teal-600 text-white border-teal-600'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Date pickers --}}
            <div class="flex items-center gap-3 mt-3">
                <div class="flex-1">
                    <input type="date" wire:model.live.debounce.500ms="startDate"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"
                        placeholder="Date début">
                </div>
                <span class="text-gray-400">au</span>
                <div class="flex-1">
                    <input type="date" wire:model.live.debounce.500ms="endDate"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"
                        placeholder="Date fin">
                </div>
            </div>
        </div>

        {{-- Sélecteur de ville --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ville</label>
            <select wire:model.live="selectedCity"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                <option value="all">Toutes les villes</option>
                @foreach ($cities as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Toggle Absolu / Normalisé --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mode</label>
            <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden {{ $isSingleCity ? 'opacity-50 cursor-not-allowed' : '' }}">
                <button wire:click="setMode('absolute')"
                    @if($isSingleCity) disabled @endif
                    class="px-4 py-2 text-sm font-medium transition-colors
                        {{ $effectiveMode === 'absolute'
                            ? 'bg-teal-600 text-white'
                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                    Absolu
                </button>
                <button wire:click="setMode('normalized')"
                    @if($isSingleCity) disabled @endif
                    class="px-4 py-2 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600
                        {{ $effectiveMode === 'normalized'
                            ? 'bg-teal-600 text-white'
                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                    Normalisé
                </button>
            </div>
        </div>
    </div>
</div>
