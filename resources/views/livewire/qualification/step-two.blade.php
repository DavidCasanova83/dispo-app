<div x-show="$wire.currentStep === 2" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-4"
    x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Étape 2 : Profil</h2>

    <!-- Profil -->
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Définir le profil <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-4 sm:grid-cols-4 gap-2">
            @foreach (['Seul', 'Couple', 'Famille', 'Groupe d\'amis', 'Groupe Scolaire', 'Groupe de voyages', 'Groupe famille'] as $profileOption)
                <button type="button" wire:click="$set('profile', {{ json_encode($profileOption) }})"
                    :disabled="$wire.profileUnknown"
                    :class="$wire.profile === {{ json_encode($profileOption) }} ?
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                    class="px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ $profileOption }}
                </button>
            @endforeach
            <button type="button" wire:click="$toggle('profileUnknown')"
                :class="$wire.profileUnknown ?
                    'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                    'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                class="px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm">
                Inconnu
            </button>
        </div>
        @error('profile')
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-3 border-gray-200 dark:border-gray-700">

    <!-- Tranches d'âge -->
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Tranche(s) d'âge correspondant <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
            @foreach (['0-6', '6-12', '12-18', '18-25', '25-40', '40-60', '60+'] as $ageOption)
                <button type="button" wire:click="toggleAgeGroup('{{ $ageOption }}')" :disabled="$wire.ageUnknown"
                    @class([
                        'px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm disabled:opacity-50 disabled:cursor-not-allowed',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => in_array($ageOption, $ageGroups),
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => !in_array($ageOption, $ageGroups),
                    ])>
                    {{ $ageOption }}
                </button>
            @endforeach
            <button type="button" wire:click="$toggle('ageUnknown')"
                :class="$wire.ageUnknown ?
                    'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' :
                    'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]'"
                class="px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm">
                Inconnu
            </button>
        </div>
        @error('ageGroups')
            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-3 border-gray-200 dark:border-gray-700">

    <!-- Caractéristiques -->
    <div class="mb-3">
        <label class="block text-sm font-semibold mb-1.5 text-gray-900 dark:text-white">
            Caractéristiques
        </label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach (['Avec un chien', 'Avec ses enfants', 'En situation de handicap'] as $charOption)
                <button type="button" wire:click="toggleCharacteristic({{ json_encode($charOption) }})"
                    @class([
                        'px-3 py-1.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => in_array($charOption, $characteristics),
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => !in_array($charOption, $characteristics),
                    ])>
                    {{ $charOption }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Navigation -->
    <x-qualification.step-navigation :currentStep="$currentStep" :totalSteps="3" :showPrevious="true" nextLabel="Suivant"
        nextAction="nextStep" previousAction="previousStep" />
</div>
