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
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 space-y-8 min-h-[500px] transition-all duration-500 ease-in-out">

                <!-- Section 1 : Informations générales -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Informations générales</h2>

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
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => $country === $countryOption,
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $country !== $countryOption
                                    ])
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
                            <div class="mt-3">
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
                        <div class="mb-6">
                            <label class="block text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                                Préciser le(s) département(s) <span class="text-red-500">*</span>
                            </label>
                            <livewire:department-selector
                                :departments="$departments"
                                :unknown="$departmentUnknown"
                                :key="'department-selector-edit-' . $qualificationId . '-' . $country"
                            />
                            @error('departments')
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
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="contact@example.com"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Consentements -->
                    <div class="space-y-3 mb-6">
                        <label class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <input
                                type="checkbox"
                                wire:model="consentNewsletter"
                                class="w-5 h-5 text-[#3E9B90] border-gray-300 rounded focus:ring-2 focus:ring-[#3E9B90] mt-0.5"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">
                                La personne souhaite recevoir la <strong>newsletter</strong> et des informations sur les événements.
                            </span>
                        </label>

                        <label class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
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
                            @foreach (['Seul', 'Couple', 'Famille', 'Groupe d\'amis'] as $profileOption)
                                <button
                                    type="button"
                                    wire:click="$set('profile', {{ json_encode($profileOption) }})"
                                    {{ $profileUnknown ? 'disabled' : '' }}
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => $profile === $profileOption,
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => $profile !== $profileOption,
                                        'disabled:opacity-50 disabled:cursor-not-allowed' => $profileUnknown
                                    ])
                                >
                                    {{ $profileOption }}
                                </button>
                            @endforeach
                        </div>
                        <button
                            type="button"
                            wire:click="$toggle('profileUnknown')"
                            @class([
                                'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                                'bg-[#3E9B90] text-white shadow-md' => $profileUnknown,
                                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !$profileUnknown
                            ])
                        >
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
                                <button
                                    type="button"
                                    wire:click="toggleAgeGroup('{{ $ageOption }}')"
                                    {{ $ageUnknown ? 'disabled' : '' }}
                                    @class([
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95',
                                        'bg-[#3E9B90] text-white shadow-md scale-105' => in_array($ageOption, $ageGroups),
                                        'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !in_array($ageOption, $ageGroups),
                                        'disabled:opacity-50 disabled:cursor-not-allowed' => $ageUnknown
                                    ])
                                >
                                    {{ $ageOption }}
                                </button>
                            @endforeach
                        </div>
                        <button
                            type="button"
                            wire:click="$toggle('ageUnknown')"
                            @class([
                                'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                                'bg-[#3E9B90] text-white shadow-md' => $ageUnknown,
                                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' => !$ageUnknown
                            ])
                        >
                            Inconnu
                        </button>
                        @error('ageGroups')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Section 3 : Demandes -->
                <div class="pb-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Demandes</h2>

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
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all resize-none"
                                placeholder="Précisez votre demande..."
                            ></textarea>
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
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                            <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="cancel"
                        class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-3 bg-[#3E9B90] hover:bg-[#357f76] text-white rounded-lg font-medium transition-colors shadow-md"
                    >
                        Sauvegarder
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
