<x-layouts.app :title="__('Logs d\'activité')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        
        <!-- Section Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total d'événements</p>
                        <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
                    </div>
                    <div class="text-3xl">📊</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Aujourd'hui</p>
                        <p class="text-2xl font-bold">{{ $stats['today'] }}</p>
                    </div>
                    <div class="text-3xl">📅</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Erreurs</p>
                        <p class="text-2xl font-bold">{{ $stats['errors'] }}</p>
                    </div>
                    <div class="text-3xl">❌</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Par statut</p>
                        <div class="text-xs">
                            @foreach ($stats['by_status'] as $status => $count)
                                <div class="flex justify-between">
                                    <span>{{ ucfirst($status) }}:</span>
                                    <span class="font-semibold">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-3xl">🔍</div>
                </div>
            </div>
        </div>

        <!-- Section Filtres -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filtres</h3>
                <div class="flex space-x-2">
                    @if (request()->hasAny(['event_type', 'status', 'entity_type', 'date_from', 'date_to', 'search']))
                        <a href="{{ route('logs.index') }}" 
                           class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            🔄 Effacer les filtres
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logs.clear') }}" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer les logs de plus de 30 jours ?')"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            🗑️ Nettoyer les logs
                        </button>
                    </form>
                </div>
            </div>

            <form method="GET" action="{{ route('logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Recherche -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🔍 Rechercher dans les messages
                        </label>
                        <input name="search" type="text" value="{{ request('search') }}"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            placeholder="Rechercher...">
                    </div>

                    <!-- Type d'événement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            📝 Type d'événement
                        </label>
                        <select name="event_type"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Tous les types</option>
                            @foreach ($eventTypes as $type)
                                <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🎯 Statut
                        </label>
                        <select name="status"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Tous les statuts</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type d'entité -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏷️ Type d'entité
                        </label>
                        <select name="entity_type"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">Tous les types</option>
                            @foreach ($entityTypes as $type)
                                <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Date de début -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            📅 Date de début
                        </label>
                        <input name="date_from" type="date" value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Date de fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            📅 Date de fin
                        </label>
                        <input name="date_to" type="date" value="{{ request('date_to') }}"
                            class="w-full px-3 py-2 border text-black border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des logs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <span class="text-green-500 mr-2">✅</span>
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if ($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Date/Heure</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Type</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Action</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Entité</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Utilisateur</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Statut</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Message</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        <div class="flex items-center">
                                            <span class="mr-2">{{ $log->event_icon }}</span>
                                            <span class="text-gray-900 dark:text-white">{{ $log->event_type }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $log->action }}
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        @if ($log->entity_type)
                                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                {{ $log->entity_type }}
                                                @if ($log->entity_id)
                                                    :{{ $log->entity_id }}
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        @if ($log->user)
                                            {{ $log->user->name }}
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">Public</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $log->status_color }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                        {{ $log->description }}
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        <a href="{{ route('logs.show', $log) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                            🔍 Détails
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📊</div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun log trouvé</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Aucun log ne correspond aux critères de recherche.
                    </p>
                    <a href="{{ route('logs.index') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Effacer les filtres
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>