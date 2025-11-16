<div x-show="$wire.currentStep === 2" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-4"
    x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Étape 2 : Profil</h2>

    <!-- Profil -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Définir le profil <span class="text-red-500">*</span>
        </label>
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach (['Seul', 'Couple', 'Famille', 'Groupe d\'amis', 'Groupe Scolaire', 'Groupe de voyages', 'Groupe famille'] as $profileOption)
                <button type="button" wire:click="$set('profile', {{ json_encode($profileOption) }})"
                    :disabled="$wire.profileUnknown"
                    :class="$wire.profile === {{ json_encode($profileOption) }} ?
                        'bg-[#3E9B90] text-white shadow-md scale-105' :
                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ $profileOption }}
                </button>
            @endforeach
        </div>
        <button type="button" wire:click="$toggle('profileUnknown')"
            :class="$wire.profileUnknown ?
                'bg-[#3E9B90] text-white shadow-md' :
                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="px-4 py-2 rounded-lg font-medium transition-all duration-200">
            Inconnu
        </button>
        @error('profile')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-6 border-gray-300 dark:border-gray-600">

    <!-- Tranches d'âge -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Tranche(s) d'âge correspondant <span class="text-red-500">*</span>
        </label>
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach (['0-18', '18-25', '25-40', '40-60', '60+'] as $ageOption)
                <button type="button" wire:click="toggleAgeGroup('{{ $ageOption }}')" :disabled="$wire.ageUnknown"
                    @class([
                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed',
                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array(
                            $ageOption,
                            $ageGroups),
                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array(
                            $ageOption,
                            $ageGroups),
                    ])>
                    {{ $ageOption }}
                </button>
            @endforeach
        </div>
        <button type="button" wire:click="$toggle('ageUnknown')"
            :class="$wire.ageUnknown ?
                'bg-[#3E9B90] text-white shadow-md' :
                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="px-4 py-2 rounded-lg font-medium transition-all duration-200">
            Inconnu
        </button>
        @error('ageGroups')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Navigation -->
    <x-qualification.step-navigation :currentStep="$currentStep" :totalSteps="3" :showPrevious="true" nextLabel="Suivant"
        nextAction="nextStep" previousAction="previousStep" />
</div>
