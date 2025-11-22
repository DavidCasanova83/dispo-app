<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Commander des images</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Remplissez le formulaire ci-dessous pour commander vos images
            </p>
        </div>

        {{-- Message de succès --}}
        @if($showSuccessMessage)
            <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 p-6 border border-green-200 dark:border-green-800">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Commande confirmée !</h3>
                        <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                            Votre commande <strong>{{ $orderNumber }}</strong> a été enregistrée avec succès.
                            Vous allez recevoir un email de confirmation à l'adresse indiquée.
                        </p>
                    </div>
                    <button type="button" wire:click="closeSuccessMessage" class="text-green-600 dark:text-green-400 hover:text-green-800">
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
            {{-- Formulaire --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informations client</h2>

                {{-- Type de client --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Êtes-vous un professionnel ou un particulier ? <span class="text-red-500">*</span>
                    </label>
                    <select
                        wire:model.live="customer_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"
                    >
                        <option value="">-- Sélectionnez une option --</option>
                        <option value="particulier">Particulier</option>
                        <option value="professionnel">Professionnel</option>
                    </select>
                    @error('customer_type')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Langue --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Choisissez votre langue favorite <span class="text-red-500">*</span>
                    </label>
                    <select
                        wire:model="language"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                    >
                        <option value="francais">Français</option>
                        <option value="anglais">Anglais</option>
                        <option value="neerlandais">Néerlandais</option>
                        <option value="italien">Italien</option>
                        <option value="allemand">Allemand</option>
                        <option value="espagnol">Espagnol</option>
                    </select>
                    @error('language')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Société (seulement si professionnel) --}}
                @if($customer_type === 'professionnel')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Société <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="company"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="Nom de la société"
                        >
                        @error('company')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                {{-- Civilité --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Civilité <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="radio" wire:model="civility" value="mr" class="mr-2 text-[#3E9B90] focus:ring-[#3E9B90]">
                            <span class="text-gray-700 dark:text-gray-300">Mr</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" wire:model="civility" value="mme" class="mr-2 text-[#3E9B90] focus:ring-[#3E9B90]">
                            <span class="text-gray-700 dark:text-gray-300">Mme</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" wire:model="civility" value="autre" class="mr-2 text-[#3E9B90] focus:ring-[#3E9B90]">
                            <span class="text-gray-700 dark:text-gray-300">Autre</span>
                        </label>
                    </div>
                    @error('civility')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Nom et Prénom --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="last_name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="Nom"
                        >
                        @error('last_name')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Prénom <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="first_name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="Prénom"
                        >
                        @error('first_name')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Adresse --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adresse 1 <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model.blur="address_line1"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                        placeholder="Adresse 1"
                    >
                    @error('address_line1')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adresse 2
                    </label>
                    <input
                        type="text"
                        wire:model.blur="address_line2"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                        placeholder="Adresse 2 (optionnel)"
                    >
                </div>

                {{-- Code postal et Ville --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Code Postal <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="postal_code"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="Code Postal"
                        >
                        @error('postal_code')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ville <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.blur="city"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="Ville"
                        >
                        @error('city')
                            <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Pays --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pays <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model.blur="country"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                        placeholder="Pays"
                    >
                    @error('country')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mail <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        wire:model.blur="email"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                        placeholder="exemple@email.com"
                    >
                    @error('email')
                        <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Indicatif pays
                        </label>
                        <input
                            type="text"
                            wire:model="phone_country_code"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="+33"
                        >
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Numéro de téléphone
                        </label>
                        <input
                            type="tel"
                            wire:model="phone_number"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                            placeholder="6 12 34 56 78"
                        >
                    </div>
                </div>
            </div>

            {{-- Sélection des images --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Images disponibles</h2>

                @if($customer_type === 'particulier' && count($cart) > 0)
                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            En tant que particulier, vous ne pouvez commander qu'une seule image.
                        </p>
                    </div>
                @endif

                @if($availableImages->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                        Aucune image disponible pour le moment.
                    </p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($availableImages as $image)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
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
                                        <div class="mt-2 flex items-center gap-2">
                                            <input
                                                type="number"
                                                min="1"
                                                max="{{ $image->quantity_available }}"
                                                value="{{ $cart[$image->id] }}"
                                                wire:change="updateCartQuantity({{ $image->id }}, $event.target.value)"
                                                class="w-16 px-2 py-1 text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                                        <button
                                            type="button"
                                            wire:click="addToCart({{ $image->id }}, 1)"
                                            class="mt-2 w-full px-3 py-1.5 bg-[#3E9B90] text-white text-sm rounded-lg hover:bg-[#357f76] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            @if($customer_type === 'particulier' && count($cart) > 0) disabled @endif
                                        >
                                            Ajouter
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Panier --}}
            @if(!empty($cartItems))
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Votre sélection</h2>

                    <div class="space-y-3">
                        @foreach($cartItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
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
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Commentaires ou remarques (optionnel)
                </label>
                <textarea
                    wire:model="customer_notes"
                    rows="4"
                    maxlength="1000"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#3E9B90]"
                    placeholder="Ajoutez vos commentaires ici..."
                ></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ strlen($customer_notes) }}/1000 caractères
                </p>
            </div>

            {{-- Bouton de soumission --}}
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="px-8 py-3 bg-[#3E9B90] hover:bg-[#357f76] text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Envoyer ma commande</span>
                    <span wire:loading>Envoi en cours...</span>
                </button>
            </div>
        </form>
    </div>
</div>
