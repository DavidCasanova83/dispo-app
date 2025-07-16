<x-layouts.app :title="__('Détails du log')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('logs.index') }}" 
                   class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    ← Retour aux logs
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $log->event_icon }} Détails du log #{{ $log->id }}
                </h1>
            </div>
            <span class="px-3 py-1 text-sm rounded-full {{ $log->status_color }}">
                {{ ucfirst($log->status) }}
            </span>
        </div>

        <!-- Main Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Type d'événement
                        </label>
                        <div class="flex items-center">
                            <span class="mr-2">{{ $log->event_icon }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $log->event_type }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Action
                        </label>
                        <span class="text-gray-900 dark:text-white">{{ $log->action }}</span>
                    </div>

                    @if ($log->entity_type)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Entité
                            </label>
                            <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ $log->entity_type }}
                                @if ($log->entity_id)
                                    :{{ $log->entity_id }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if ($log->user)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Utilisateur
                            </label>
                            <div class="flex items-center">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg mr-2">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ $log->user->initials() }}
                                    </span>
                                </span>
                                <div>
                                    <span class="text-gray-900 dark:text-white">{{ $log->user->name }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm block">{{ $log->user->email }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date et heure
                        </label>
                        <span class="text-gray-900 dark:text-white">{{ $log->created_at->format('d/m/Y à H:i:s') }}</span>
                    </div>

                    @if ($log->ip_address)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Adresse IP
                            </label>
                            <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ $log->ip_address }}
                            </span>
                        </div>
                    @endif

                    @if ($log->user_agent)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                User Agent
                            </label>
                            <span class="text-sm text-gray-900 dark:text-white break-all">
                                {{ $log->user_agent }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Statut
                        </label>
                        <span class="px-2 py-1 text-xs rounded-full {{ $log->status_color }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message -->
        @if ($log->message || $log->description)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Message</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <p class="text-gray-900 dark:text-white">
                        {{ $log->message ?: $log->description }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Additional Data -->
        @if ($log->data)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Données supplémentaires</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap overflow-x-auto">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('logs.index') }}" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                Retour à la liste
            </a>
            @if ($log->entity_type === 'accommodation' && $log->entity_id)
                <a href="{{ route('accommodations') }}?search={{ $log->entity_id }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Voir l'hébergement
                </a>
            @endif
        </div>
    </div>
</x-layouts.app>