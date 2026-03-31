<div x-show="$wire.currentStep === 3" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-x-4"
    x-transition:enter-end="opacity-100 transform translate-x-0">
    <h2 class="text-2xl font-bold mb-8 text-gray-900 dark:text-white">Étape 3 : Demandes</h2>

    <!-- Date d'ajout du formulaire -->
    <div class="mb-8" x-data>
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Date
        </label>
        <div class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg w-fit">
            <!-- Hidden native date input -->
            <input type="date" wire:model.live="addedDate" x-ref="dateInput" class="sr-only">

            <!-- Displayed date + clickable icon -->
            <span class="text-gray-900 dark:text-white text-sm font-medium">
                {{ $addedDate ? \Carbon\Carbon::parse($addedDate)->format('d/m/Y') : 'Sélectionner une date' }}
            </span>

            <button type="button" @click="$refs.dateInput.showPicker()"
                class="text-[#3E9B90] hover:text-[#2d7a72] transition-colors">
                <flux:icon.calendar variant="mini" />
            </button>
        </div>

        @error('addedDate')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <!-- Méthode de contact -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Méthode de contact <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
            @foreach (['Direct', 'Téléphone', 'Email', 'Site web', 'Réseaux sociaux'] as $method)
                <button type="button" wire:click="setContactMethod('{{ $method }}')"
                    @class([
                        'px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => $contactMethod === $method,
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => $contactMethod !== $method,
                    ])>
                    {{ $method }}
                </button>
            @endforeach
        </div>
    </div>

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <!-- Demandes spécifiques -->
    @if ($this->citySpecificOptions)
        <div class="mb-8">
            <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
                Demande spécifique à <span class="text-[#3E9B90]">{{ $cityName }}</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($this->citySpecificOptions as $option)
                    <button type="button" wire:click="toggleSpecificRequest('{{ addslashes($option) }}')"
                        @class([
                            'px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm',
                            'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => in_array($option, $specificRequests),
                            'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => !in_array($option, $specificRequests),
                        ])>
                        {{ $option }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Autres demandes spécifiques (des autres villes) -->
    <div class="mb-8" x-data="{ show: @entangle('showOtherSpecificDropdown') }">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Autres demandes spécifiques
        </label>

        <!-- Chips des demandes sélectionnées -->
        @if (count($otherSpecificRequests) > 0)
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach ($otherSpecificRequests as $request)
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#3E9B90] text-white rounded-lg text-sm font-medium">
                        {{ $request }}
                        <button type="button" wire:click="removeOtherSpecificRequest('{{ addslashes($request) }}')"
                            class="hover:bg-white/20 rounded-full p-0.5 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Bouton Autre pour ouvrir le dropdown -->
        <div class="relative">
            <button type="button" @click="show = !show" wire:click="openOtherSpecificDropdown"
                @class([
                    'px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-sm inline-flex items-center gap-1',
                    'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => count($otherSpecificRequests) > 0,
                    'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => count($otherSpecificRequests) === 0,
                ])>
                Autre
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown -->
            <div x-show="show" @click.away="show = false; $wire.closeOtherSpecificDropdown()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="absolute z-10 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
                style="display: none;">
                <div class="p-3">
                    <!-- Champ de recherche -->
                    <input type="text" wire:model.live.debounce.300ms="otherSpecificSearchQuery"
                        placeholder="Rechercher..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all">
                </div>

                <!-- Liste scrollable des options -->
                <div class="max-h-60 overflow-y-auto border-t border-gray-200 dark:border-gray-700">
                    @if (count($this->filteredOtherSpecificOptions) > 0)
                        @foreach ($this->filteredOtherSpecificOptions as $option)
                            <button type="button" wire:click="toggleOtherSpecificRequest('{{ addslashes($option) }}')"
                                class="w-full px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between text-sm">
                                <span class="text-gray-900 dark:text-white">{{ $option }}</span>
                                @if (in_array($option, $otherSpecificRequests))
                                    <svg class="w-5 h-5 text-[#3E9B90]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    @else
                        <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            Aucune demande trouvée
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <!-- Demandes générales -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Demande générale <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach ($generalOptions as $option)
                <button type="button" wire:click="toggleGeneralRequest('{{ addslashes($option) }}')"
                    @class([
                        'px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-center text-sm',
                        'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => in_array($option, $generalRequests),
                        'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => !in_array($option, $generalRequests),
                    ])>
                    {{ $option }}
                </button>
            @endforeach
        </div>

        <!-- Chips des "Autre" sélectionnées -->
        @if (count($otherGeneralRequests) > 0)
            <div class="flex flex-wrap gap-2 mt-3">
                @foreach ($otherGeneralRequests as $request)
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#3E9B90] text-white rounded-lg text-sm font-medium">
                        {{ $request }}
                        <button type="button" wire:click="toggleOtherGeneralRequest({{ json_encode($request) }})"
                            class="hover:bg-white/20 rounded-full p-0.5 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Bouton Autre avec dropdown -->
        <div class="relative mt-3" x-data="{ showGeneralOther: false }">
            <button type="button" @click="showGeneralOther = !showGeneralOther"
                @class([
                    'px-4 py-2.5 rounded-lg font-medium transition-all duration-200 border text-sm inline-flex items-center gap-1',
                    'bg-[#3E9B90] text-white shadow-md border-[#3E9B90]' => count($otherGeneralRequests) > 0,
                    'bg-white text-gray-700 border-gray-300 hover:border-[#3E9B90] hover:text-[#3E9B90] dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:border-[#3E9B90]' => count($otherGeneralRequests) === 0,
                ])>
                Autre
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="showGeneralOther" @click.away="showGeneralOther = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="absolute z-10 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
                style="display: none;">
                <div class="max-h-60 overflow-y-auto">
                    @foreach (['Patou', 'Pluies', 'Enfant en bas age'] as $option)
                        <button type="button" wire:click="toggleOtherGeneralRequest({{ json_encode($option) }})"
                            class="w-full px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between text-sm">
                            <span class="text-gray-900 dark:text-white">{{ $option }}</span>
                            @if (in_array($option, $otherGeneralRequests))
                                <svg class="w-5 h-5 text-[#3E9B90]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @error('generalRequests')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <!-- Autres demandes -->
    <div class="mb-8">
        <label class="block text-base font-semibold mb-3 text-gray-900 dark:text-white">
            Autres, à préciser
        </label>
        <div class="relative">
            <textarea wire:model.blur="otherRequest" rows="3" maxlength="1000"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all resize-none"
                placeholder="Précisez votre demande..."></textarea>
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
    <x-qualification.step-navigation :currentStep="$currentStep" :totalSteps="3" :showPrevious="true" nextLabel="Envoyer"
        nextAction="submit" previousAction="previousStep" />
</div>
