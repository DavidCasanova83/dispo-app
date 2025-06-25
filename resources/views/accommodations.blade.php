<x-layouts.app :title="__('Hébergements')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hébergements</h1>
            <div class="flex gap-2">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    ← Dashboard
                </a>
                <button onclick="location.reload()"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    🔄 Actualiser
                </button>
            </div>
        </div>

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
                        <p class="text-2xl font-bold">{{ $stats['by_type']->count() }}</p>
                        <p class="text-xs opacity-90">{{ $stats['by_type']->sum() }} établissements</p>
                    </div>
                    <div class="text-3xl">🏷️</div>
                </div>
            </div>

            <!-- Par ville -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Villes</p>
                        <p class="text-2xl font-bold">{{ $stats['by_city']->count() }}</p>
                        <p class="text-xs opacity-90">{{ $stats['by_city']->sum() }} établissements</p>
                    </div>
                    <div class="text-3xl">🏙️</div>
                </div>
            </div>
        </div>

        <!-- Informations de contact -->
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
        </div>

        <!-- Top 5 des villes -->
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
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            @if ($accommodations->count() > 0)
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accommodations as $accommodation)
                        <div
                            class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $accommodation->name }}</h3>
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    @if ($accommodation->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200
                                    @elseif($accommodation->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                                    @else bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200 @endif">
                                    {{ ucfirst($accommodation->status) }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                @if ($accommodation->city)
                                    <div class="flex items-center text-gray-600 dark:text-gray-300">
                                        <span class="mr-2">🏙️</span>
                                        <span>{{ $accommodation->city }}</span>
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

                                <div class="flex items-center text-gray-500 dark:text-gray-400">
                                    <span class="mr-2">🆔</span>
                                    <span class="font-mono text-xs">{{ $accommodation->apidae_id }}</span>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Créé le {{ $accommodation->created_at->format('d/m/Y à H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div
                    class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center">
                        <span class="text-blue-600 dark:text-blue-400 mr-2">ℹ️</span>
                        <p class="text-blue-800 dark:text-blue-200 text-sm">
                            <strong>{{ $accommodations->count() }}</strong> hébergement(s) trouvé(s).
                            Utilisez la commande <code class="bg-blue-200 dark:bg-blue-800 px-1 rounded">php artisan
                                apidae:fetch --test</code> pour ajouter des données de test.
                        </p>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🏨</div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun hébergement trouvé</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Aucun hébergement n'a été récupéré depuis l'API Apidae.
                    </p>
                    <div
                        class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                            Pour tester, exécutez : <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">php
                                artisan apidae:fetch --test</code>
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
