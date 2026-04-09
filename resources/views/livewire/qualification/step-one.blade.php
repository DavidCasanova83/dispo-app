<div x-show="$wire.currentStep === 1" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-4"
    x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Étape 1 : Informations générales</h2>

    <!-- Type de visiteur -->
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Type de visiteur <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 gap-2">
            @foreach (['Touriste', 'Habitant', 'Socio Pro'] as $typeOption)
                <button type="button" wire:click="$set('visitorType', '{{ $typeOption }}')"
                    :class="$wire.visitorType === '{{ $typeOption }}' ?
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                    class="px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm">
                    {{ $typeOption }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Pays de résidence (sélection multiple) -->
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Quel(s) pays de résidence ? <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
            @foreach (['France', 'Belgique', 'Allemagne', 'Italie', 'Pays-Bas', 'Suisse', 'Espagne', 'Angleterre'] as $countryOption)
                <button type="button" wire:click="toggleCountry('{{ $countryOption }}')"
                    @class([
                        'px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => in_array($countryOption, $countries),
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => !in_array($countryOption, $countries),
                    ])>
                    {{ $countryOption }}
                </button>
            @endforeach

            {{-- Bouton "Autre" : ouvre le dropdown --}}
            <div class="relative" x-data="{ show: @entangle('showCountryDropdown') }">
                <button type="button" @click="show = !show" wire:click="openCountryDropdown"
                    @class([
                        'w-full px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm inline-flex items-center justify-center gap-1',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => count($this->otherSelectedCountries) > 0,
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => count($this->otherSelectedCountries) === 0,
                    ])>
                    Autre
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="show" @click.away="show = false; $wire.closeCountryDropdown()"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="absolute z-20 right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
                    style="display: none;">
                    <div class="p-3">
                        <input type="text" wire:model.live.debounce.300ms="countrySearchQuery"
                            placeholder="Rechercher un pays..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all">
                    </div>
                    <div class="max-h-60 overflow-y-auto border-t border-gray-200 dark:border-gray-700">
                        @forelse ($this->filteredCountryOptions as $option)
                            <button type="button" wire:click="addOtherCountry('{{ addslashes($option) }}')"
                                class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between text-sm">
                                <span class="text-gray-900 dark:text-white">{{ $option }}</span>
                                @if (in_array($option, $countries))
                                    <svg class="w-5 h-5 text-[#3E9B90]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                Aucun pays trouvé
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Chips des pays "Autre" sélectionnés --}}
        @if (count($this->otherSelectedCountries) > 0)
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach ($this->otherSelectedCountries as $selected)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#3E9B90] text-white rounded-lg text-xs font-medium">
                        {{ $selected }}
                        <button type="button" wire:click="removeCountry('{{ addslashes($selected) }}')"
                            class="hover:bg-white/20 rounded-full p-0.5 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        @endif

        @error('countries')
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Département -->
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Préciser le(s) département(s)
        </label>
        <livewire:department-selector :departments="$departments" :unknown="$departmentUnknown" :key="'department-selector-' . $formResetKey" />
        @error('departments')
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Navigation -->
    <x-qualification.step-navigation :currentStep="$currentStep" :totalSteps="3" :showPrevious="false" nextLabel="Suivant"
        nextAction="nextStep" />
</div>
