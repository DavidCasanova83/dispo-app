<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Header -->
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('qualification.city.data', $city) }}"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                ← Retour à la liste
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Édition de Qualification</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $cityName }}</p>
    </div>

    <!-- Messages flash -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Formulaire d'édition -->
    <div class="max-w-5xl mx-auto w-full">
        <form wire:submit.prevent="save">
            <div
                class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 space-y-8 min-h-[500px] transition-all duration-500 ease-in-out">

                <!-- Section 1 : Informations générales -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Informations générales</h2>

                    <!-- Type de visiteur -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Type de visiteur <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Touriste', 'Habitant', 'Socio Pro'] as $typeOption)
                                <button type="button" wire:click="$set('visitorType', '{{ $typeOption }}')"
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                            $visitorType === $typeOption,
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                            $visitorType !== $typeOption,
                                    ])>
                                    {{ $typeOption }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pays de résidence (sélection multiple) -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Quel(s) pays de résidence ? <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2 items-center">
                            @foreach (['France', 'Belgique', 'Allemagne', 'Italie', 'Pays-Bas', 'Suisse', 'Espagne', 'Angleterre'] as $countryOption)
                                <button type="button" wire:click="toggleCountry('{{ $countryOption }}')"
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array($countryOption, $countries),
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array($countryOption, $countries),
                                    ])>
                                    {{ $countryOption }}
                                </button>
                            @endforeach

                            {{-- Bouton "Autre" : ouvre le dropdown --}}
                            <div class="relative" x-data="{ show: @entangle('showCountryDropdown') }">
                                <button type="button" @click="show = !show" wire:click="openCountryDropdown"
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 inline-flex items-center gap-1',
                                        'bg-[#3E9B90] text-white shadow-md' => count($this->otherSelectedCountries) > 0,
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => count($this->otherSelectedCountries) === 0,
                                    ])>
                                    Autre
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <div x-show="show" @click.away="show = false; $wire.closeCountryDropdown()"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 transform scale-100"
                                    x-transition:leave-end="opacity-0 transform scale-95"
                                    class="absolute z-20 left-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
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
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach ($this->otherSelectedCountries as $selected)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#3E9B90] text-white rounded-lg text-sm font-medium">
                                        {{ $selected }}
                                        <button type="button" wire:click="removeCountry('{{ addslashes($selected) }}')"
                                            class="hover:bg-white/20 rounded-full p-0.5 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @error('countries')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Département -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Préciser le(s) département(s)
                        </label>
                        <livewire:department-selector :departments="$departments" :unknown="$departmentUnknown" :key="'department-selector-edit-' . $qualificationId" />
                        @error('departments')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <!-- Section 2 : Profil -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Profil</h2>

                    <!-- Profil -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Définir le profil <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach (['Seul', 'Couple', 'Famille', 'Groupe d\'amis', 'Groupe Scolaire', 'Groupe de voyages', 'Groupe famille'] as $profileOption)
                                <button type="button" wire:click="$set('profile', {{ json_encode($profileOption) }})"
                                    {{ $profileUnknown ? 'disabled' : '' }} @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                            $profile === $profileOption,
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                            $profile !== $profileOption,
                                        'disabled:opacity-50 disabled:cursor-not-allowed' => $profileUnknown,
                                    ])>
                                    {{ $profileOption }}
                                </button>
                            @endforeach
                        </div>
                        <button type="button" wire:click="$toggle('profileUnknown')" @class([
                            'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                            'bg-[#3E9B90] text-white shadow-md' => $profileUnknown,
                            'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !$profileUnknown,
                        ])>
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
                            @foreach (['0-6', '6-12', '12-18', '18-25', '25-40', '40-60', '60+'] as $ageOption)
                                <button type="button" wire:click="toggleAgeGroup('{{ $ageOption }}')"
                                    {{ $ageUnknown ? 'disabled' : '' }} @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array(
                                            $ageOption,
                                            $ageGroups),
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array(
                                            $ageOption,
                                            $ageGroups),
                                        'disabled:opacity-50 disabled:cursor-not-allowed' => $ageUnknown,
                                    ])>
                                    {{ $ageOption }}
                                </button>
                            @endforeach
                        </div>
                        <button type="button" wire:click="$toggle('ageUnknown')" @class([
                            'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                            'bg-[#3E9B90] text-white shadow-md' => $ageUnknown,
                            'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !$ageUnknown,
                        ])>
                            Inconnu
                        </button>
                        @error('ageGroups')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <hr class="my-6 border-gray-300 dark:border-gray-600">

                    <!-- Caractéristiques -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Caractéristiques
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Avec un chien', 'Avec ses enfants', 'En situation de handicap'] as $charOption)
                                <button type="button" wire:click="toggleCharacteristic({{ json_encode($charOption) }})"
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array(
                                            $charOption,
                                            $characteristics),
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array(
                                            $charOption,
                                            $characteristics),
                                    ])>
                                    {{ $charOption }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Section 3 : Demandes -->
                <div class="pb-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Demandes</h2>

                    <!-- Date d'ajout du formulaire -->
                    <div class="mb-6" x-data>
                        <div class="flex items-center gap-2">
                            <!-- Hidden native date input -->
                            <input type="date" wire:model.live="addedDate" x-ref="dateInput" class="sr-only">

                            <!-- Displayed date + clickable icon -->
                            <span class="text-gray-900 dark:text-white">
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

                    <hr class="my-6 border-gray-300 dark:border-gray-600">

                    <!-- Méthode de contact -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Méthode de contact <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="setContactMethod('Direct')" @class([
                                'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                    $contactMethod === 'Direct',
                                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                    $contactMethod !== 'Direct',
                            ])>
                                Direct
                            </button>
                            <button type="button" wire:click="setContactMethod('Téléphone')"
                                @class([
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                    'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                        $contactMethod === 'Téléphone',
                                    'text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                        $contactMethod !== 'Téléphone',
                                ])>
                                Téléphone
                            </button>
                            <button type="button" wire:click="setContactMethod('Email')" @class([
                                'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                'bg-[#3E9B90] text-white shadow-md scale-105' => $contactMethod === 'Email',
                                'text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                    $contactMethod !== 'Email',
                            ])>
                                Email
                            </button>
                            <button type="button" wire:click="setContactMethod('Site web')"
                                @class([
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                    'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                        $contactMethod === 'Site web',
                                    'text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                        $contactMethod !== 'Site web',
                                ])>
                                Site web
                            </button>
                            <button type="button" wire:click="setContactMethod('Réseaux sociaux')"
                                @class([
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                    'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                        $contactMethod === 'Réseaux sociaux',
                                    'text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                        $contactMethod !== 'Réseaux sociaux',
                                ])>
                                Réseaux sociaux
                            </button>
                        </div>
                    </div>

                    <hr class="my-6 border-gray-300 dark:border-gray-600">

                    <!-- Demandes spécifiques -->
                    @if ($this->citySpecificOptions)
                        <div class="mb-6">
                            <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                                Demande spécifique à <span class="text-[#3E9B90]">{{ $cityName }}</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->citySpecificOptions as $option)
                                    <button type="button"
                                        wire:click="toggleSpecificRequest('{{ addslashes($option) }}')"
                                        @class([
                                            'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                            'bg-[#3E9B90] text-white shadow-md scale-105' => in_array(
                                                $option,
                                                $specificRequests),
                                            'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array(
                                                $option,
                                                $specificRequests),
                                        ])>
                                        {{ $option }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Autres demandes spécifiques (des autres villes) -->
                    <div class="mb-6" x-data="{ show: @entangle('showOtherSpecificDropdown') }">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Autres demandes spécifiques
                        </label>

                        <!-- Chips des demandes sélectionnées -->
                        @if (count($otherSpecificRequests) > 0)
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach ($otherSpecificRequests as $request)
                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1 bg-[#3E9B90] text-white rounded-lg text-sm">
                                        {{ $request }}
                                        <button type="button"
                                            wire:click="removeOtherSpecificRequest('{{ addslashes($request) }}')"
                                            class="hover:bg-white/20 rounded-full p-0.5 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
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
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                    'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                        count($otherSpecificRequests) > 0,
                                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                        count($otherSpecificRequests) === 0,
                                ])>
                                Autre
                                <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
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
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all">
                                </div>

                                <!-- Liste scrollable des options -->
                                <div class="max-h-60 overflow-y-auto border-t border-gray-200 dark:border-gray-700">
                                    @if (count($this->filteredOtherSpecificOptions) > 0)
                                        @foreach ($this->filteredOtherSpecificOptions as $option)
                                            <button type="button"
                                                wire:click="toggleOtherSpecificRequest('{{ addslashes($option) }}')"
                                                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-between">
                                                <span class="text-gray-900 dark:text-white">{{ $option }}</span>
                                                @if (in_array($option, $otherSpecificRequests))
                                                    <svg class="w-5 h-5 text-[#3E9B90]" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
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

                    <hr class="my-6 border-gray-300 dark:border-gray-600">

                    <!-- Demandes générales -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Demande générale <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($generalOptions as $option)
                                <button type="button" wire:click="toggleGeneralRequest('{{ addslashes($option) }}')"
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array(
                                            $option,
                                            $generalRequests),
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array(
                                            $option,
                                            $generalRequests),
                                    ])>
                                    {{ $option }}
                                </button>
                            @endforeach
                        </div>
                        <!-- Chips des "Autre" sélectionnées -->
                        @if (count($otherGeneralRequests) > 0)
                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach ($otherGeneralRequests as $request)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#3E9B90] text-white rounded-lg text-sm">
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
                                    'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                    'bg-[#3E9B90] text-white shadow-md scale-105' =>
                                        count($otherGeneralRequests) > 0,
                                    'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' =>
                                        count($otherGeneralRequests) === 0,
                                ])>
                                Autre
                                <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-between">
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

                    <hr class="my-6 border-gray-300 dark:border-gray-600">

                    <!-- Autres demandes -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                            Autres, à préciser
                        </label>
                        <div class="relative">
                            <textarea wire:model.blur="otherRequest" rows="3" maxlength="1000"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all resize-none"
                                placeholder="Précisez votre demande..."></textarea>
                            <div class="absolute bottom-2 right-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ strlen($otherRequest) }} / 1000
                            </div>
                        </div>
                        @error('otherRequest')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Message d'erreur général -->
                    @error('requests')
                        <div
                            class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                            <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="cancel"
                        class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-[#3E9B90] hover:bg-[#357f76] text-white rounded-lg font-medium transition-colors shadow-md">
                        Sauvegarder
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
