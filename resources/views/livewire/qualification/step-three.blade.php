<div x-show="$wire.currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Étape 3 : Demandes</h2>

    <!-- Date d'ajout du formulaire -->
    <div class="mb-6" x-data>
        <div class="flex items-center gap-2">
            <!-- Hidden native date input -->
            <input
                type="date"
                wire:model.live="addedDate"
                x-ref="dateInput"
                class="sr-only"
            >

            <!-- Displayed date + clickable icon -->
            <span class="text-gray-900 dark:text-white">
                {{ $addedDate ? \Carbon\Carbon::parse($addedDate)->format('d/m/Y') : 'Sélectionner une date' }}
            </span>

            <button
                type="button"
                @click="$refs.dateInput.showPicker()"
                class="text-[#3E9B90] hover:text-[#2d7a72] transition-colors"
            >
                <flux:icon.calendar variant="mini" />
            </button>
        </div>

        @error('addedDate')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-6 border-gray-300 dark:border-gray-600">

    <!-- Méthode de contact -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Méthode de contact <span class="text-red-500">*</span>
        </label>
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="setContactMethod('Direct')"
                @class([
                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                    'bg-[#3E9B90] text-white shadow-md scale-105' => $contactMethod === 'Direct',
                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $contactMethod !== 'Direct'
                ])
            >
                Direct
            </button>
            <button
                type="button"
                wire:click="setContactMethod('Téléphone')"
                @class([
                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                    'bg-[#3E9B90] text-white shadow-md scale-105' => $contactMethod === 'Téléphone',
                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $contactMethod !== 'Téléphone'
                ])
            >
                Téléphone
            </button>
            <button
                type="button"
                wire:click="setContactMethod('Email')"
                @class([
                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                    'bg-[#3E9B90] text-white shadow-md scale-105' => $contactMethod === 'Email',
                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $contactMethod !== 'Email'
                ])
            >
                Email
            </button>
        </div>
    </div>

    <hr class="my-6 border-gray-300 dark:border-gray-600">

    <!-- Demandes spécifiques -->
    @if($this->citySpecificOptions)
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                Demande spécifique à <span class="text-[#3E9B90]">{{ $cityName }}</span>
            </label>
            <div class="flex flex-wrap gap-2">
                @foreach($this->citySpecificOptions as $option)
                    <button
                        type="button"
                        wire:click="toggleSpecificRequest('{{ addslashes($option) }}')"
                        @class([
                            'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                            'bg-[#3E9B90] text-white shadow-md scale-105' => in_array($option, $specificRequests),
                            'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array($option, $specificRequests)
                        ])
                    >
                        {{ $option }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Demandes générales -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Demande générale <span class="text-red-500">*</span>
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach($generalOptions as $option)
                <button
                    type="button"
                    wire:click="toggleGeneralRequest('{{ addslashes($option) }}')"
                    @class([
                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array($option, $generalRequests),
                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array($option, $generalRequests)
                    ])
                >
                    {{ $option }}
                </button>
            @endforeach
        </div>
        @error('generalRequests')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-6 border-gray-300 dark:border-gray-600">

    <!-- Autres demandes -->
    <div class="mb-6">
        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
            Autres, à préciser
        </label>
        <div class="relative">
            <textarea
                wire:model.blur="otherRequest"
                rows="3"
                maxlength="1000"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all resize-none"
                placeholder="Précisez votre demande..."
            ></textarea>
            <div class="absolute bottom-2 right-2 text-xs text-gray-500 dark:text-gray-400">
                <span x-text="$wire.otherRequest.length"></span> / 1000
            </div>
        </div>
        @error('otherRequest')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Message d'erreur général -->
    @error('requests')
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
        </div>
    @enderror

    <!-- Navigation -->
    <x-qualification.step-navigation
        :currentStep="$currentStep"
        :totalSteps="3"
        :showPrevious="true"
        nextLabel="Envoyer"
        nextAction="submit"
        previousAction="previousStep"
    />
</div>
