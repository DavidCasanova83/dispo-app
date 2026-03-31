<div x-show="$wire.currentStep === 1" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-4"
    x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-2xl font-bold mb-8 text-gray-900 dark:text-white">Étape 1 : Informations générales</h2>

    <!-- Type de visiteur -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Type de visiteur <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 gap-2">
            @foreach (['Touriste', 'Habitant', 'Socio Pro'] as $typeOption)
                <button type="button" wire:click="$set('visitorType', '{{ $typeOption }}')"
                    :class="$wire.visitorType === '{{ $typeOption }}' ?
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                    class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm">
                    {{ $typeOption }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Pays de résidence -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Quel est le pays de résidence ? <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
            @foreach (['France', 'Belgique', 'Allemagne', 'Italie', 'Pays-Bas', 'Suisse', 'Espagne', 'Angleterre', 'Autre'] as $countryOption)
                <button type="button" wire:click="$set('country', '{{ $countryOption }}')"
                    :class="$wire.country === '{{ $countryOption }}' ?
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                    class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm">
                    {{ $countryOption }}
                </button>
            @endforeach
        </div>
        @error('country')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <!-- Champ pour autre pays -->
        @if ($country === 'Autre')
            <div class="mt-3" x-transition>
                <livewire:country-selector :country="$otherCountry" :key="'country-selector-' . $country" />
                @error('otherCountry')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <!-- Département -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Préciser le(s) département(s)
        </label>
        <livewire:department-selector :departments="$departments" :unknown="$departmentUnknown" :key="'department-selector-' . $country . '-' . $formResetKey" />
        @error('departments')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Navigation -->
    <x-qualification.step-navigation :currentStep="$currentStep" :totalSteps="3" :showPrevious="false" nextLabel="Suivant"
        nextAction="nextStep" />
</div>
