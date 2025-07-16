<x-layouts.app :title="__('Hébergements')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        
        <!-- Section Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
                                    <span>{{ ucfirst($status) }}:</span>
                                    <span class="font-semibold">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-3xl">📊</div>
                </div>
            </div>

            <!-- Par type -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Types</p>
                        <p class="text-2xl font-bold">{{ count($stats['by_type']) }}</p>
                        <p class="text-xs opacity-90">{{ array_sum($stats['by_type']) }} établissements</p>
                    </div>
                    <div class="text-3xl">🏷️</div>
                </div>
            </div>

            <!-- Par ville -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Villes</p>
                        <p class="text-2xl font-bold">{{ count($stats['by_city']) }}</p>
                        <p class="text-xs opacity-90">{{ array_sum($stats['by_city']) }} établissements</p>
                    </div>
                    <div class="text-3xl">🏙️</div>
                </div>
            </div>
        </div>

        <!-- Section Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filtres</h3>
                @if (request()->hasAny(['search', 'status', 'city', 'type', 'has_email', 'has_phone', 'has_website']))
                    <a href="{{ route('accommodations') }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        🔄 Effacer les filtres
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('accommodations') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Recherche par nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🔍 Rechercher par nom
                        </label>
                        <input name="search" type="text" value="{{ $filters['search'] ?? '' }}"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            placeholder="Nom de l'hébergement...">
                    </div>

                    <!-- Filtre par statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            📊 Statut
                        </label>
                        <select name="status"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Tous les statuts</option>
                            @foreach ($filterOptions['statuses'] as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtre par ville -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏙️ Ville
                        </label>
                        <select name="city"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Toutes les villes</option>
                            @foreach ($filterOptions['cities'] as $city)
                                <option value="{{ $city }}" {{ ($filters['city'] ?? '') === $city ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtre par type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏷️ Type
                        </label>
                        <select name="type"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Tous les types</option>
                            @foreach ($filterOptions['types'] as $type)
                                <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filtres pour les informations de contact -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center">
                        <input name="has_email" type="checkbox" value="1" {{ ($filters['has_email'] ?? false) ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">📧 Avec email</span>
                    </label>
                    <label class="flex items-center">
                        <input name="has_phone" type="checkbox" value="1" {{ ($filters['has_phone'] ?? false) ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">📞 Avec téléphone</span>
                    </label>
                    <label class="flex items-center">
                        <input name="has_website" type="checkbox" value="1" {{ ($filters['has_website'] ?? false) ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">🌐 Avec site web</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        @if (!empty($topCities))
        <!-- Top 5 des villes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 mb-6">
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
        @endif

        <!-- Liste des hébergements -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            @if ($accommodations->count() > 0)
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accommodations as $accommodation)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $accommodation->name }}</h3>
                                    <a href="{{ $accommodation->getManageUrl() }}" 
                                       target="_blank"
                                       title="Gérer le statut de cet hébergement"
                                       class="inline-flex items-center justify-center text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </a>
                                </div>
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    @if ($accommodation->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200
                                    @elseif($accommodation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                                    @else bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200 @endif">
                                    {{ $accommodation->status_label }}
                                </span>
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
                                            {{ $accommodation->formatted_phone ?? $accommodation->phone }}
                                        </a>
                                    </div>
                                @endif

                                @if ($accommodation->website)
                                    <div class="flex items-center text-gray-600 dark:text-gray-300">
                                        <span class="mr-2">🌐</span>
                                        <a href="{{ $accommodation->safe_website }}" target="_blank" rel="noopener noreferrer"
                                            class="text-blue-600 dark:text-blue-400 hover:underline">
                                            Site web
                                        </a>
                                    </div>
                                @endif

                                <div class="flex items-center text-gray-500 dark:text-gray-400">
                                    <span class="mr-2">🆔</span>
                                    <span class="font-mono text-xs">{{ $accommodation->apidae_id }}</span>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Créé le {{ $accommodation->formatted_created_at }}
                                </div>
                                @if ($accommodation->isRecent())
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200 rounded-full">
                                        Nouveau
                                    </span>
                                @endif
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
                    <a href="{{ route('accommodations') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Effacer les filtres
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>