<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Commander des brochures</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Remplissez le formulaire ci-dessous pour commander vos brochures
            </p>
        </div>

        {{-- Message de succès --}}
        @if($showSuccessMessage)
            <div class="mb-6 rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">Commande confirmée !</h3>
                        <p class="mt-1 text-white">
                            Votre commande <strong>{{ $orderNumber }}</strong> a été enregistrée avec succès.
                            Vous allez recevoir un email de confirmation à l'adresse indiquée.
                        </p>
                    </div>
                    <button type="button" wire:click="closeSuccessMessage" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="submitOrder" class="space-y-6">
            {{-- Protection Honeypot anti-spam --}}
            <x-honeypot wire:model="honeypot" />

            {{-- Formulaire --}}
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8 space-y-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Informations client</h2>

                {{-- Type de client --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Êtes-vous un professionnel ou un particulier ? <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            wire:click="$set('customer_type', 'particulier')"
                            class="flex-1 px-6 py-3 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $customer_type === 'particulier' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Particulier
                        </button>
                        <button
                            type="button"
                            wire:click="$set('customer_type', 'professionnel')"
                            class="flex-1 px-6 py-3 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $customer_type === 'professionnel' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Professionnel
                        </button>
                    </div>
                    @error('customer_type')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Langue --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Choisissez votre langue favorite <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <button
                            type="button"
                            wire:click="$set('language', 'francais')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'francais' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Français
                        </button>
                        <button
                            type="button"
                            wire:click="$set('language', 'anglais')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'anglais' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Anglais
                        </button>
                        <button
                            type="button"
                            wire:click="$set('language', 'neerlandais')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'neerlandais' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Néerlandais
                        </button>
                        <button
                            type="button"
                            wire:click="$set('language', 'italien')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'italien' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Italien
                        </button>
                        <button
                            type="button"
                            wire:click="$set('language', 'allemand')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'allemand' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Allemand
                        </button>
                        <button
                            type="button"
                            wire:click="$set('language', 'espagnol')"
                            class="px-4 py-2.5 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $language === 'espagnol' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Espagnol
                        </button>
                    </div>
                    @error('language')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Société (seulement si professionnel) --}}
                @if($customer_type === 'professionnel')
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Société <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="company"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="Nom de la société"
                        >
                        @error('company')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <hr class="my-6 border-gray-200 dark:border-gray-600">

                {{-- Civilité --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Civilité <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            wire:click="$set('civility', 'mr')"
                            class="flex-1 px-6 py-3 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $civility === 'mr' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Mr
                        </button>
                        <button
                            type="button"
                            wire:click="$set('civility', 'mme')"
                            class="flex-1 px-6 py-3 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $civility === 'mme' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Mme
                        </button>
                        <button
                            type="button"
                            wire:click="$set('civility', 'autre')"
                            class="flex-1 px-6 py-3 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 active:scale-95 {{ $civility === 'autre' ? 'bg-[#3E9B90] text-white shadow-md scale-105' : 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 border border-gray-300 dark:border-transparent' }}"
                        >
                            Autre
                        </button>
                    </div>
                    @error('civility')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Nom et Prénom --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Nom <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="last_name"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="Nom"
                        >
                        @error('last_name')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Prénom <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="first_name"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="Prénom"
                        >
                        @error('first_name')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-6 border-gray-200 dark:border-gray-600">

                {{-- Adresse --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Adresse 1 <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model.blur="address_line1"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                        placeholder="Adresse 1"
                    >
                    @error('address_line1')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Adresse 2
                    </label>
                    <input
                        type="text"
                        wire:model.blur="address_line2"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                        placeholder="Adresse 2 (optionnel)"
                    >
                </div>

                {{-- Code postal et Ville --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Code Postal <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="postal_code"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="Code Postal"
                        >
                        @error('postal_code')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Ville <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="city"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="Ville"
                        >
                        @error('city')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Pays --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model.blur="country"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                        placeholder="Pays"
                    >
                    @error('country')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <hr class="my-6 border-gray-200 dark:border-gray-600">

                {{-- Email --}}
                <div>
                    <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Mail <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        wire:model.blur="email"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                        placeholder="exemple@email.com"
                    >
                    @error('email')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Indicatif pays
                        </label>
                        <input
                            type="text"
                            wire:model="phone_country_code"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="+33"
                        >
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Numéro de téléphone
                        </label>
                        <input
                            type="tel"
                            wire:model="phone_number"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                            placeholder="6 12 34 56 78"
                        >
                    </div>
                </div>
            </div>

            {{-- Sélection des images --}}
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Brochures disponibles</h2>

                {{-- Messages d'information selon le type de client --}}
                @if($customer_type === 'particulier')
                    <div class="mb-6 p-4 bg-blue-100 dark:bg-blue-900/20 rounded-lg border border-blue-300 dark:border-blue-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-blue-800 dark:text-blue-200">Particulier</p>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                    Vous pouvez commander <strong>1 seule brochure</strong> (quantité fixe : 1).
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif($customer_type === 'professionnel')
                    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/20 rounded-lg border border-green-300 dark:border-green-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-green-800 dark:text-green-200">Professionnel</p>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                    Vous pouvez commander <strong>plusieurs brochures</strong> et choisir la <strong>quantité désirée</strong> pour chaque brochure.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-700">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Veuillez d'abord sélectionner si vous êtes un <strong>particulier</strong> ou un <strong>professionnel</strong> pour commander des brochures.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($availableImages->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        Aucune brochure disponible pour le moment.
                    </p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($availableImages as $image)
                            <div class="border border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-transparent shadow-sm">
                                <img
                                    src="{{ asset('storage/' . $image->thumbnail_path) }}"
                                    alt="{{ $image->title }}"
                                    class="w-full h-32 object-cover"
                                >
                                <div class="p-3">
                                    <h3 class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                        {{ $image->title ?? $image->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Disponible: {{ $image->quantity_available }}
                                    </p>

                                    @if(isset($cart[$image->id]))
                                        {{-- Image déjà dans le panier --}}
                                        <div class="mt-2 flex items-center gap-2">
                                            <input
                                                type="number"
                                                min="1"
                                                max="{{ $image->quantity_available }}"
                                                value="{{ $cart[$image->id] }}"
                                                wire:change="updateCartQuantity({{ $image->id }}, $event.target.value)"
                                                class="w-16 px-2 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            >
                                            <button
                                                type="button"
                                                wire:click="removeFromCart({{ $image->id }})"
                                                class="text-xs text-red-600 dark:text-red-400 hover:underline"
                                            >
                                                Retirer
                                            </button>
                                        </div>
                                    @else
                                        {{-- Ajout au panier --}}
                                        @if($customer_type === 'professionnel')
                                            {{-- Pour les pros: champ quantité + bouton ajouter --}}
                                            <div class="mt-2 space-y-2">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="{{ $image->quantity_available }}"
                                                    value="1"
                                                    wire:model.defer="quantities.{{ $image->id }}"
                                                    class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"
                                                    placeholder="Quantité"
                                                >
                                                <button
                                                    type="button"
                                                    wire:click="addToCart({{ $image->id }}, $wire.quantities[{{ $image->id }}] ?? 1)"
                                                    class="w-full px-3 py-2 bg-[#3E9B90] text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                                                >
                                                    Ajouter
                                                </button>
                                            </div>
                                        @else
                                            {{-- Pour les particuliers: bouton ajouter direct (quantité 1) --}}
                                            <button
                                                type="button"
                                                wire:click="addToCart({{ $image->id }}, 1)"
                                                class="mt-2 w-full px-3 py-2 bg-[#3E9B90] text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                                                @if(count($cart) > 0) disabled @endif
                                            >
                                                Ajouter
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Panier --}}
            @if(!empty($cartItems))
                <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Votre sélection</h2>

                    <div class="space-y-3">
                        @foreach($cartItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-transparent">
                                <div class="flex items-center gap-3">
                                    <img
                                        src="{{ asset('storage/' . $item['image']->thumbnail_path) }}"
                                        alt="{{ $item['image']->title }}"
                                        class="w-12 h-12 object-cover rounded"
                                    >
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $item['image']->title ?? $item['image']->name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Quantité: {{ $item['quantity'] }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $item['image']->id }})"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
                <label class="block text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Commentaires ou remarques (optionnel)
                </label>
                <textarea
                    wire:model="customer_notes"
                    rows="4"
                    maxlength="1000"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent transition-all"
                    placeholder="Ajoutez vos commentaires ici..."
                ></textarea>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    {{ strlen($customer_notes) }}/1000 caractères
                </p>
            </div>

            {{-- Protection CAPTCHA Cloudflare Turnstile --}}
            @if(config('turnstile.turnstile_site_key') && config('turnstile.turnstile_secret_key'))
                <div class="bg-white dark:bg-[#001716] shadow-lg rounded-lg p-8">
                    <div class="flex justify-center">
                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ config('turnstile.turnstile_site_key') }}"
                            data-theme="auto"
                            data-size="normal"
                            data-callback="onTurnstileSuccess"
                        ></div>
                    </div>
                    @error('cf-turnstile-response')
                        <p class="text-sm text-red-600 dark:text-red-400 mt-2 text-center">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Bouton de soumission --}}
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="px-8 py-3 bg-[#3E9B90] text-white text-lg font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 shadow-md"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Envoyer ma commande</span>
                    <span wire:loading>Envoi en cours...</span>
                </button>
            </div>
        </form>
    </div>

    @if(config('turnstile.turnstile_site_key') && config('turnstile.turnstile_secret_key'))
        @push('scripts')
            {{-- Cloudflare Turnstile JavaScript SDK --}}
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

            <script>
                // Callback appelé lorsque le CAPTCHA est validé avec succès
                function onTurnstileSuccess(token) {
                    console.log('✅ Turnstile token reçu:', token.substring(0, 20) + '...');

                    // Envoyer le token directement à la propriété Livewire
                    const component = Livewire.find(
                        document.querySelector('[wire\\:id]').getAttribute('wire:id')
                    );

                    if (component) {
                        component.set('turnstileToken', token);
                        console.log('✅ Token envoyé à Livewire property');
                    } else {
                        console.error('❌ Composant Livewire non trouvé');
                    }
                }

                // Reset du widget après soumission
                document.addEventListener('livewire:init', () => {
                    Livewire.on('resetTurnstile', () => {
                        console.log('🔄 Reset Turnstile widget');
                        if (typeof turnstile !== 'undefined') {
                            turnstile.reset();
                        }
                    });
                });
            </script>
        @endpush
    @endif
</div>
