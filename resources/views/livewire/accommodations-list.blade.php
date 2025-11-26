<div>
    <!-- Notifications flash -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Section Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total des établissements -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total établissements</p>
                    <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
                </div>
                <div class="text-3xl">🏨</div>
            </div>
        </div>

        <!-- Par statut -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Statuts</p>
                    <div class="text-sm">
                        @foreach ($stats['by_status'] as $status => $count)
                             <div class="flex justify-between">
                                <span>{{ $this->getStatusLabel($status) }}:</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="text-3xl">📊</div>
            </div>
        </div>

        <!-- Par type -->
        <!-- <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Types</p>
                    <p class="text-2xl font-bold">{{ $stats['by_type']->count() }}</p>
                    <p class="text-xs opacity-90">{{ $stats['by_type']->sum() }} établissements</p>
                </div>
                <div class="text-3xl">🏷️</div>
            </div>
        </div> -->

        <!-- Par ville -->
        <!-- <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Villes</p>
                    <p class="text-2xl font-bold">{{ $stats['by_city']->count() }}</p>
                    <p class="text-xs opacity-90">{{ $stats['by_city']->sum() }} établissements</p>
                </div>
                <div class="text-3xl">🏙️</div>
            </div>
        </div> -->
    </div>

    {{-- <!-- Informations de contact -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="flex items-center">
                <div class="text-2xl mr-3">📧</div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $stats['with_email'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Avec email</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="flex items-center">
                <div class="text-2xl mr-3">📞</div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $stats['with_phone'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Avec téléphone</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="flex items-center">
                <div class="text-2xl mr-3">🌐</div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $stats['with_website'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Avec site web</p>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- <!-- Top 5 des villes -->
    @if ($topCities->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top 5 des villes</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @foreach ($topCities as $city => $count)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $count }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">{{ $city }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif --}}

    <!-- Section Filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 my-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-2">
                <button wire:click="sendAvailabilityEmails"
                    wire:confirm="Êtes-vous sûr de vouloir envoyer les emails de disponibilités à tous les hébergements ?"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    📧 Envoyer les mails
                </button>
                <button wire:click="clearFilters"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    🔄 Effacer les filtres
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Recherche par nom -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🔍 Rechercher par nom
                </label>
                <input wire:model.live="search" type="text"
                    class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="Nom de l'hébergement...">
            </div>

            <!-- Filtre par statut -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    📊 Statut
                </label>
                <select wire:model.live="statusFilter"
                    class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}">{{ $this->getStatusLabel($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtre par ville -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🏙️ Ville
                </label>
                <select wire:model.live="cityFilter"
                    class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Toutes les villes</option>
                    @foreach ($cityOptions as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtre par type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    🏷️ Type
                </label>
                <select wire:model.live="typeFilter"
                    class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les types</option>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filtres pour les informations de contact -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-center">
                <input wire:model.live="hasEmail" type="checkbox" class="mr-2">
                <span class="text-sm text-gray-700 dark:text-gray-300">📧 Avec email</span>
            </label>
            <label class="flex items-center">
                <input wire:model.live="hasPhone" type="checkbox" class="mr-2">
                <span class="text-sm text-gray-700 dark:text-gray-300">📞 Avec téléphone</span>
            </label>
            <label class="flex items-center">
                <input wire:model.live="hasWebsite" type="checkbox" class="mr-2">
                <span class="text-sm text-gray-700 dark:text-gray-300">🌐 Avec site web</span>
            </label>
        </div>

        <!-- Tri par nombre de réponses -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔢 Trier par
            </label>
            <select wire:model.live="sortBy"
                class="w-full md:w-64 px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="name">Nom (A-Z)</option>
                <option value="responses_desc">Réponses (+ au -)</option>
                <option value="responses_asc">Réponses (- au +)</option>
            </select>
        </div>
    </div>

    <!-- Liste des hébergements -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
        @if ($accommodations->count() > 0)
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($accommodations as $accommodation)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $accommodation->name }}</h3>
                           <span
                                class="px-2 py-1 text-xs rounded-full
                                @if ($accommodation->status === 'disponible' || $accommodation->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200
                                @elseif($accommodation->status === 'en_attente' || $accommodation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                                @else bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200 @endif">
                                {{ $this->getStatusLabel($accommodation->status) }}
                            </span>
                        </div>

                        <!-- Boutons de mise à jour manuelle du statut -->
                        <div class="flex gap-1 mb-3">
                            <button
                                wire:click="updateStatus({{ $accommodation->id }}, 'disponible')"
                                @class([
                                    'px-2 py-1 text-xs rounded transition-colors',
                                    'bg-green-500 text-white hover:bg-green-600' => $accommodation->status !== 'disponible',
                                    'bg-green-200 text-green-600 cursor-not-allowed opacity-50' => $accommodation->status === 'disponible',
                                ])
                                @if($accommodation->status === 'disponible') disabled @endif
                            >
                                ✓ Disponible
                            </button>
                            <button
                                wire:click="updateStatus({{ $accommodation->id }}, 'indisponible')"
                                @class([
                                    'px-2 py-1 text-xs rounded transition-colors',
                                    'bg-red-500 text-white hover:bg-red-600' => $accommodation->status !== 'indisponible',
                                    'bg-red-200 text-red-600 cursor-not-allowed opacity-50' => $accommodation->status === 'indisponible',
                                ])
                                @if($accommodation->status === 'indisponible') disabled @endif
                            >
                                ✗ Indisponible
                            </button>
                        </div>

                        <div class="space-y-2 text-sm">
                            @if ($accommodation->city)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <span class="mr-2">🏙️</span>
                                    <span>{{ $accommodation->city }}</span>
                                </div>
                            @endif

                            @if ($accommodation->type)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <span class="mr-2">🏷️</span>
                                    <span>{{ $accommodation->type }}</span>
                                </div>
                            @endif

                            @if ($accommodation->email)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <span class="mr-2">📧</span>
                                    <a href="mailto:{{ $accommodation->email }}"
                                        class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $accommodation->email }}
                                    </a>
                                </div>
                            @endif

                            @if ($accommodation->phone)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <span class="mr-2">📞</span>
                                    <a href="tel:{{ $accommodation->phone }}"
                                        class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $accommodation->phone }}
                                    </a>
                                </div>
                            @endif

                            @if ($accommodation->website)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <span class="mr-2">🌐</span>
                                    <a href="{{ $accommodation->website }}" target="_blank"
                                        class="text-blue-600 dark:text-blue-400 hover:underline">
                                        Site web
                                    </a>
                                </div>
                            @endif

                            <div class="flex items-center text-gray-500 dark:text-gray-400">
                                <span class="mr-2">🆔</span>
                                <span class="font-mono text-xs">{{ $accommodation->apidae_id }}</span>
                            </div>

                            {{-- Compteur de réponses --}}
                            @if ($accommodation->responses_count > 0)
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300 mt-2">
                                    <span class="mr-2">📊</span>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                            {{ $accommodation->responses_count }} réponse{{ $accommodation->responses_count > 1 ? 's' : '' }}
                                        </span>
                                        @if ($accommodation->available_responses_count > 0)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $accommodation->available_responses_count }} dispo
                                            </span>
                                        @endif
                                        @if ($accommodation->unavailable_responses_count > 0)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $accommodation->unavailable_responses_count }} indispo
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Créé le {{ $accommodation->created_at->format('d/m/Y à H:i') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $accommodations->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun hébergement trouvé</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Aucun hébergement ne correspond aux critères de recherche.
                </p>
                <button wire:click="clearFilters"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Effacer les filtres
                </button>
            </div>
        @endif
    </div>
</div>
