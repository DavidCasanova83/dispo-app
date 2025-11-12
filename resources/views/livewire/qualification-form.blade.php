<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Header -->
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-2">
        </div>
        <h1 class="text-3xl font-bold text-white">Formulaire de Qualification</h1>
        <p class="text-gray-400 mt-2">{{ $cityName }}</p>
    </div>

    <!-- Formulaire Multi-étapes -->
    <div class="max-w-4xl mx-auto w-full">
        <div class="bg-[#001716] shadow-lg rounded-lg p-6" x-data="{
            currentStep: 1,
            city: '{{ $city }}',
            showSuccessMessage: false,
        
            // Étape 1
            country: 'France',
            otherCountry: '',
            department: '',
            departmentUnknown: false,
            email: '',
            consentNewsletter: false,
            consentDataProcessing: false,
        
            // Étape 2
            profile: '',
            profileUnknown: false,
            ageGroups: [],
            ageUnknown: false,
        
            // Étape 3
            specificRequests: [],
            generalRequests: [],
            otherRequest: '',
        
            // Options
            departments: [
                '01 - Ain', '02 - Aisne', '03 - Allier', '04 - Alpes-de-Haute-Provence',
                '05 - Hautes-Alpes', '06 - Alpes-Maritimes', '07 - Ardèche', '08 - Ardennes',
                '09 - Ariège', '10 - Aube', '11 - Aude', '12 - Aveyron', '13 - Bouches-du-Rhône',
                '14 - Calvados', '15 - Cantal', '16 - Charente', '17 - Charente-Maritime',
                '18 - Cher', '19 - Corrèze', '2A - Corse-du-Sud', '2B - Haute-Corse',
                '21 - Côte-dOr', '22 - Côtes-dArmor', '23 - Creuse', '24 - Dordogne',
                '25 - Doubs', '26 - Drôme', '27 - Eure', '28 - Eure-et-Loir', '29 - Finistère',
                '30 - Gard', '31 - Haute-Garonne', '32 - Gers', '33 - Gironde', '34 - Hérault',
                '35 - Ille-et-Vilaine', '36 - Indre', '37 - Indre-et-Loire', '38 - Isère',
                '39 - Jura', '40 - Landes', '41 - Loir-et-Cher', '42 - Loire', '43 - Haute-Loire',
                '44 - Loire-Atlantique', '45 - Loiret', '46 - Lot', '47 - Lot-et-Garonne',
                '48 - Lozère', '49 - Maine-et-Loire', '50 - Manche', '51 - Marne',
                '52 - Haute-Marne', '53 - Mayenne', '54 - Meurthe-et-Moselle', '55 - Meuse',
                '56 - Morbihan', '57 - Moselle', '58 - Nièvre', '59 - Nord', '60 - Oise',
                '61 - Orne', '62 - Pas-de-Calais', '63 - Puy-de-Dôme', '64 - Pyrénées-Atlantiques',
                '65 - Hautes-Pyrénées', '66 - Pyrénées-Orientales', '67 - Bas-Rhin',
                '68 - Haut-Rhin', '69 - Rhône', '70 - Haute-Saône', '71 - Saône-et-Loire',
                '72 - Sarthe', '73 - Savoie', '74 - Haute-Savoie', '75 - Paris',
                '76 - Seine-Maritime', '77 - Seine-et-Marne', '78 - Yvelines',
                '79 - Deux-Sèvres', '80 - Somme', '81 - Tarn', '82 - Tarn-et-Garonne',
                '83 - Var', '84 - Vaucluse', '85 - Vendée', '86 - Vienne', '87 - Haute-Vienne',
                '88 - Vosges', '89 - Yonne', '90 - Territoire de Belfort', '91 - Essonne',
                '92 - Hauts-de-Seine', '93 - Seine-Saint-Denis', '94 - Val-de-Marne',
                '95 - Val-d-Oise', '971 - Guadeloupe', '972 - Martinique', '973 - Guyane',
                '974 - La Réunion', '976 - Mayotte'
            ],
        
            specificOptions: {
                'annot': ['Escalade', 'Train à Vapeur', 'Grès d\'Annot'],
                'colmars-les-alpes': ['Lac d Allos', 'Cascade de la Lance', 'Maison Musée'],
                'entrevaux': ['Nice', 'Cote dazur', 'Chemin de ronde', 'Citadelle', 'Gorge de Daluis', 'Train à Vapeur'],
                'la-palud-sur-verdon': ['Blanc-Martel', 'Route des Crêtes', 'Escalade et via cordatta'],
                'saint-andre-les-alpes': ['Lac de Castillon', 'Parapente', 'Train à vapeur']
            },
        
            generalOptions: [
                'Randonnées', 'Pêche', 'Train', 'Villages alentours', 'Patrimoine culturel', 'Patrimoine naturel',
                'Visite guidée', 'Accès et transports', 'Informations pratiques', 'Evènements et animations',
                'Baignade et nautisme', 'Boutique Verdon Tourisme', 'Activité d\'eau vive', 'Vélo et VTT',
                'Autres activités de pleine nature', 'Commerces', 'Produits locaux', 'Restaurants',
                'Hébergements', 'Sociaux pro', 'Demandes d\'habitants'
            ],
        
            init() {
                // Charger les données depuis localStorage
                const saved = localStorage.getItem('qualification_' + this.city);
                if (saved) {
                    const data = JSON.parse(saved);
                    Object.assign(this, data);
                }
            },
        
            saveToLocalStorage() {
                const data = {
                    currentStep: this.currentStep,
                    country: this.country,
                    otherCountry: this.otherCountry,
                    department: this.department,
                    departmentUnknown: this.departmentUnknown,
                    email: this.email,
                    consentNewsletter: this.consentNewsletter,
                    consentDataProcessing: this.consentDataProcessing,
                    profile: this.profile,
                    profileUnknown: this.profileUnknown,
                    ageGroups: this.ageGroups,
                    ageUnknown: this.ageUnknown,
                    specificRequests: this.specificRequests,
                    generalRequests: this.generalRequests,
                    otherRequest: this.otherRequest
                };
                localStorage.setItem('qualification_' + this.city, JSON.stringify(data));
            },
        
            nextStep() {
                this.saveToLocalStorage();
                this.currentStep++;
            },
        
            previousStep() {
                this.currentStep--;
            },
        
            resetForm() {
                // Réinitialiser l'étape
                this.currentStep = 1;
        
                // Réinitialiser Étape 1
                this.country = 'France';
                this.otherCountry = '';
                this.department = '';
                this.departmentUnknown = false;
                this.email = '';
                this.consentNewsletter = false;
                this.consentDataProcessing = false;
        
                // Réinitialiser Étape 2
                this.profile = '';
                this.profileUnknown = false;
                this.ageGroups = [];
                this.ageUnknown = false;
        
                // Réinitialiser Étape 3
                this.specificRequests = [];
                this.generalRequests = [];
                this.otherRequest = '';
        
                // Nettoyer le localStorage
                localStorage.removeItem('qualification_' + this.city);
        
                // Afficher le message de succès
                this.showSuccessMessage = true;
        
                // Masquer le message après 3 secondes
                setTimeout(() => {
                    this.showSuccessMessage = false;
                }, 3000);
            },
        
            async submit() {
                const formData = {
                    city: this.city,
                    country: this.country === 'Autre' ? this.otherCountry : this.country,
                    department: this.country === 'France' ? this.department : null,
                    email: this.email,
                    consentNewsletter: this.consentNewsletter,
                    consentDataProcessing: this.consentDataProcessing,
                    profile: this.profile,
                    ageGroups: this.ageGroups,
                    specificRequests: this.specificRequests,
                    generalRequests: this.generalRequests,
                    otherRequest: this.otherRequest
                };
        
                try {
                    const response = await fetch('{{ route('qualification.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(formData)
                    });
        
                    const result = await response.json();
        
                    if (result.success) {
                        // Réinitialiser le formulaire pour une nouvelle saisie
                        this.resetForm();
                    } else {
                        alert('Erreur lors de la sauvegarde');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la sauvegarde');
                }
            }
        }">

            <!-- Indicateur d'étapes -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex items-center text-sm"
                                :class="currentStep >= 1 ? 'text-[#3E9B90]' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                                    :class="currentStep >= 1 ? 'border-[#3E9B90] bg-[#3E9B90] text-white' : 'border-gray-300'">
                                    1
                                </div>
                                <span class="ml-2 hidden md:inline">Informations</span>
                            </div>
                            <div class="flex-1 h-1 mx-2" :class="currentStep >= 2 ? 'bg-[#3E9B90]' : 'bg-gray-300'">
                            </div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex items-center text-sm"
                                :class="currentStep >= 2 ? 'text-[#3E9B90]' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                                    :class="currentStep >= 2 ? 'border-[#3E9B90] bg-[#3E9B90] text-white' : 'border-gray-300'">
                                    2
                                </div>
                                <span class="ml-2 hidden md:inline">Profil</span>
                            </div>
                            <div class="flex-1 h-1 mx-2" :class="currentStep >= 3 ? 'bg-[#3E9B90]' : 'bg-gray-300'">
                            </div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-end">
                            <div class="flex items-center text-sm"
                                :class="currentStep >= 3 ? 'text-[#3E9B90]' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2"
                                    :class="currentStep >= 3 ? 'border-[#3E9B90] bg-[#3E9B90] text-white' : 'border-gray-300'">
                                    3
                                </div>
                                <span class="ml-2 hidden md:inline">Demandes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message de succès -->
            <div x-show="showSuccessMessage" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform translate-y-2"
                class="mb-6 p-4 bg-green-900 border border-green-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-green-200 font-semibold">Qualification enregistrée avec
                        succès ! Vous pouvez en saisir une nouvelle.</span>
                </div>
            </div>

            <!-- Étape 1 : Informations générales -->
            <div x-show="currentStep === 1" x-transition>
                <h2 class="text-2xl font-bold mb-6 text-white">Étape 1 : Informations générales</h2>

                <!-- Pays de résidence -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Quel est le pays de
                        résidence ?</label>
                    <div class="flex flex-wrap gap-2">
                        <template
                            x-for="option in ['France', 'Belgique', 'Allemagne', 'Italie', 'Pays-Bas', 'Suisse', 'Espagne', 'Angleterre', 'Autre']"
                            :key="option">
                            <button type="button"
                                x-on:click="country = option; if(option !== 'Autre') otherCountry = ''"
                                :class="country === option ? 'bg-[#3E9B90] text-white' :
                                    'bg-gray-700 text-white'"
                                class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Champ pour autre pays -->
                    <template x-if="country === 'Autre'">
                        <div class="mt-3">
                            <input type="text" x-model="otherCountry"
                                class="w-full px-4 py-2 border border-gray-600 rounded bg-gray-800 text-white"
                                placeholder="Précisez le pays...">
                        </div>
                    </template>
                </div>

                <!-- Département (si France) -->
                <template x-if="country === 'France'">
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-white">Préciser le
                            département</label>
                        <div class="flex gap-2 mb-2">
                            <button type="button"
                                x-on:click="departmentUnknown = !departmentUnknown; if(departmentUnknown) department = 'Inconnu'; else department = ''"
                                :class="departmentUnknown ? 'bg-[#3E9B90] text-white' :
                                    'bg-gray-700 text-white'"
                                class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                                Inconnu
                            </button>
                        </div>
                        <input type="text" x-model="department" list="departmentsList"
                            class="w-full px-4 py-2 border border-gray-600 rounded bg-gray-800 text-white"
                            placeholder="Ex: 04 - Alpes-de-Haute-Provence" :disabled="departmentUnknown">
                        <datalist id="departmentsList">
                            <template x-for="dept in departments" :key="dept">
                                <option x-text="dept"></option>
                            </template>
                        </datalist>
                    </div>
                </template>

                <hr class="my-6 border-gray-600">

                <!-- Email -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Email
                        (optionnel)</label>
                    <input type="email" x-model="email"
                        class="w-full px-4 py-2 border border-gray-600 rounded bg-gray-800 text-white"
                        placeholder="contact@example.com">
                </div>

                <!-- Consentements -->
                <div class="space-y-3 mb-6">
                    <label class="flex items-start space-x-3 p-4 bg-gray-800 rounded">
                        <input type="checkbox" x-model="consentNewsletter"
                            class="w-5 h-5 text-[#3E9B90] border-gray-300 rounded">
                        <span class="text-sm text-gray-300">
                            La personne souhaite recevoir la <strong>newsletter</strong> et des informations sur les
                            événements.
                        </span>
                    </label>

                    <label class="flex items-start space-x-3 p-4 bg-gray-800 rounded">
                        <input type="checkbox" x-model="consentDataProcessing"
                            class="w-5 h-5 text-[#3E9B90] border-gray-300 rounded">
                        <span class="text-sm text-gray-300">
                            J'accepte que mes données soient traitées conformément à la politique de confidentialité
                            RGPD.
                        </span>
                    </label>
                </div>

                <button type="button" x-on:click="nextStep()"
                    class="w-full bg-[#3E9B90] hover:bg-[#2E7B71] text-white font-bold py-3 px-6 rounded-lg transition-colors">
                    Suivant
                </button>
            </div>

            <!-- Étape 2 : Profil -->
            <div x-show="currentStep === 2" x-transition>
                <h2 class="text-2xl font-bold mb-6 text-white">Étape 2 : Profil</h2>

                <!-- Profil -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Définir le
                        profil</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <template x-for="option in ['Seul', 'Couple', 'Famille', 'Groupe d\'amis']"
                            :key="option">
                            <button type="button" x-on:click="profile = option; profileUnknown = false"
                                :class="profile === option ? 'bg-[#3E9B90] text-white' :
                                    'bg-gray-700 text-white'"
                                class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors"
                                :disabled="profileUnknown">
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>
                    <button type="button"
                        x-on:click="profileUnknown = !profileUnknown; if(profileUnknown) profile = 'Inconnu'; else profile = ''"
                        :class="profileUnknown ? 'bg-[#3E9B90] text-white' :
                            'bg-gray-700 text-white'"
                        class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                        Inconnu
                    </button>
                </div>

                <hr class="my-6 border-gray-600">

                <!-- Tranches d'âge -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Tranche(s) d'âge
                        correspondant</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <template x-for="option in ['0-18','18-25', '25-40', '40-60', '60+']" :key="option">
                            <button type="button"
                                x-on:click="ageGroups.includes(option) ? ageGroups.splice(ageGroups.indexOf(option), 1) : ageGroups.push(option)"
                                :class="ageGroups.includes(option) ? 'bg-[#3E9B90] text-white' :
                                    'bg-gray-700 text-white'"
                                class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors" :disabled="ageUnknown">
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>
                    <button type="button"
                        x-on:click="ageUnknown = !ageUnknown; if(ageUnknown) ageGroups = ['Inconnu']; else ageGroups = []"
                        :class="ageUnknown ? 'bg-[#3E9B90] text-white' :
                            'bg-gray-700 text-white'"
                        class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                        Inconnu
                    </button>
                </div>

                <div class="flex gap-4">
                    <button type="button" x-on:click="previousStep()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                        Précédent
                    </button>
                    <button type="button"
                        x-on:click="if(profile) nextStep(); else alert('Veuillez sélectionner un profil')"
                        class="flex-1 bg-[#3E9B90] hover:bg-[#2E7B71] text-white font-bold py-3 px-6 rounded-lg transition-colors">
                        Suivant
                    </button>
                </div>
            </div>

            <!-- Étape 3 : Demandes -->
            <div x-show="currentStep === 3" x-transition>
                <h2 class="text-2xl font-bold mb-6 text-white">Étape 3 : Demandes</h2>

                <!-- Demandes spécifiques -->
                <template x-if="specificOptions[city]">
                    <div class="mb-6">
                        <label class="block text-lg font-semibold mb-3 text-white">Demande
                            spécifique à <span x-text="city"></span></label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="option in specificOptions[city]" :key="option">
                                <button type="button"
                                    x-on:click="specificRequests.includes(option) ? specificRequests.splice(specificRequests.indexOf(option), 1) : specificRequests.push(option)"
                                    :class="specificRequests.includes(option) ? 'bg-[#3E9B90] text-white' :
                                        'bg-gray-700 text-white'"
                                    class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                                    <span x-text="option"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Demandes générales -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Demande
                        générale</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="option in generalOptions" :key="option">
                            <button type="button"
                                x-on:click="generalRequests.includes(option) ? generalRequests.splice(generalRequests.indexOf(option), 1) : generalRequests.push(option)"
                                :class="generalRequests.includes(option) ? 'bg-[#3E9B90] text-white' :
                                    'bg-gray-700 text-white'"
                                class="px-4 py-2 rounded hover:bg-[#4FB3A7] transition-colors">
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <hr class="my-6 border-gray-600">

                <!-- Autres demandes -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-3 text-white">Autres, à
                        préciser</label>
                    <textarea x-model="otherRequest" rows="3"
                        class="w-full px-4 py-2 border border-gray-600 rounded bg-gray-800 text-white"
                        placeholder="Précisez votre demande..."></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="button" x-on:click="previousStep()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                        Précédent
                    </button>
                    <button type="button"
                        x-on:click="if(specificRequests.length > 0 || generalRequests.length > 0 || otherRequest.trim() !== '') { submit(); } else { alert('Veuillez sélectionner au moins une demande'); }"
                        class="flex-1 bg-[#3E9B90] hover:bg-[#2E7B71] text-white font-bold py-3 px-6 rounded-lg transition-colors">
                        Envoyer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
