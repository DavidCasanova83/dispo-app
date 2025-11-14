<div x-data="{
    open: @entangle('showDropdown'),
    highlightedIndex: @entangle('highlightedIndex'),
}"
@click.away="$wire.closeDropdown()"
@delayedBlur.window="setTimeout(() => { $wire.closeDropdown() }, 200)"
class="relative w-full">

    <!-- Unknown Button -->
    <div class="flex gap-2 mb-3">
        <button
            type="button"
            wire:click="toggleUnknown"
            :class="$wire.departmentUnknown ?
                'bg-[#3E9B90] text-white shadow-md' :
                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
        >
            Inconnu
        </button>
    </div>

    <!-- Search Input -->
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="searchQuery"
            wire:keydown.arrow-down.prevent="handleKeydown('ArrowDown')"
            wire:keydown.arrow-up.prevent="handleKeydown('ArrowUp')"
            wire:keydown.enter.prevent="handleKeydown('Enter')"
            wire:keydown.escape="handleKeydown('Escape')"
            @focus="$wire.focusSearch()"
            @blur="$wire.blurSearch()"
            :disabled="$wire.departmentUnknown"
            placeholder="Ex: 04 - Alpes-de-Haute-Provence ou rechercher..."
            class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            autocomplete="off"
        >

        <!-- Search Icon -->
        <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Dropdown Results -->
    <div
        x-show="open && !$wire.departmentUnknown"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto"
        role="listbox"
    >
        @if(count($results) > 0)
            <ul class="py-1">
                @foreach($results as $index => $department)
                    <li
                        wire:click="selectDepartment('{{ $department['code'] }}')"
                        :class="highlightedIndex === {{ $index }} ?
                            'bg-[#3E9B90] text-white' :
                            'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="px-4 py-2 cursor-pointer transition-colors duration-150 flex justify-between items-center"
                        role="option"
                        :aria-selected="highlightedIndex === {{ $index }}"
                    >
                        <span class="font-medium">{{ $department['code'] }} - {{ $department['name'] }}</span>
                        <span
                            :class="highlightedIndex === {{ $index }} ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
                            class="text-sm transition-colors duration-150"
                        >
                            {{ $department['region'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                Aucun département trouvé
            </div>
        @endif
    </div>

    <!-- Keyboard Navigation Helper (hidden but accessible) -->
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
        @if($showDropdown && count($results) > 0)
            {{ count($results) }} département(s) trouvé(s). Utilisez les flèches haut/bas pour naviguer, Entrée pour sélectionner.
        @endif
    </div>
</div>
