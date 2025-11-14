<div x-show="$wire.currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Étape 1 : Informations générales</h2>

    <!-- Pays de résidence -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Quel est le pays de résidence ? <span class="text-red-500">*</span>
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach(['France', 'Belgique', 'Allemagne', 'Italie', 'Pays-Bas', 'Suisse', 'Espagne', 'Angleterre', 'Autre'] as $countryOption)
                <button
                    type="button"
                    wire:click="$set('country', '{{ $countryOption }}')"
                    :class="$wire.country === '{{ $countryOption }}' ?
                        'bg-[#3E9B90] text-white shadow-md scale-105' :
                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95"
                >
                    {{ $countryOption }}
                </button>
            @endforeach
        </div>
        @error('country')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <!-- Champ pour autre pays -->
        @if($country === 'Autre')
            <div class="mt-3" x-transition>
                <livewire:country-selector
                    :country="$otherCountry"
                    :key="'country-selector-' . $country"
                />
                @error('otherCountry')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <!-- Département (si France) -->
    @if($country === 'France')
        <div class="mb-6" x-transition>
            <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                Préciser le département <span class="text-red-500">*</span>
            </label>
            <livewire:department-selector
                :department="$department"
                :unknown="$departmentUnknown"
                :key="'department-selector-' . $country"
            />
            @error('department')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <hr class="my-6 border-gray-300 dark:border-gray-600">

    <!-- Email -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Email <span class="text-sm text-gray-500 font-normal">(optionnel)</span>
        </label>
        <input
            type="email"
            wire:model.blur="email"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
            placeholder="contact@example.com"
        >
        @error('email')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Consentements -->
    <div class="space-y-3 mb-6">
        <label class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors">
            <input
                type="checkbox"
                wire:model="consentNewsletter"
                class="w-5 h-5 text-[#3E9B90] border-gray-300 rounded focus:ring-2 focus:ring-[#3E9B90] mt-0.5"
            >
            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">
                La personne souhaite recevoir la <strong>newsletter</strong> et des informations sur les événements.
            </span>
        </label>

        <label class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors">
            <input
                type="checkbox"
                wire:model="consentDataProcessing"
                class="w-5 h-5 text-[#3E9B90] border-gray-300 rounded focus:ring-2 focus:ring-[#3E9B90] mt-0.5"
            >
            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">
                J'accepte que mes données soient traitées conformément à la politique de confidentialité RGPD.
            </span>
        </label>
    </div>

    <!-- Navigation -->
    <x-qualification.step-navigation
        :currentStep="$currentStep"
        :totalSteps="3"
        :showPrevious="false"
        nextLabel="Suivant"
        nextAction="nextStep"
    />
</div>
