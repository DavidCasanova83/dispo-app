<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Formulaires de contact</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Consultez les messages reçus depuis le site WordPress
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Non lus</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['unread'] }}</p>
                </div>
                <div class="rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-3">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aujourd'hui</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today'] }}</p>
                </div>
                <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cette semaine</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['this_week'] }}</p>
                </div>
                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-3">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <div class="grid gap-4 md:grid-cols-2">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rechercher</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nom, email, établissement..."
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select
                    wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="all">Tous</option>
                    <option value="unread">Non lus</option>
                    <option value="read">Lus</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Submissions Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Visiteur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Établissement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Apidae ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($submissions as $submission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !$submission->is_read ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($submission->is_read)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Lu
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        Nouveau
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $submission->visiteur_prenom }} {{ $submission->visiteur_nom }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $submission->visiteur_email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $submission->etablissement_nom ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $submission->etablissement_apidae_id ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $submission->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    wire:click="openDetailModal({{ $submission->id }})"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                    title="Voir le détail"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="text-gray-400 dark:text-gray-500">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="text-sm font-medium">Aucune soumission trouvée</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            {{ $submissions->links() }}
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedSubmission)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Détail de la soumission
                        </h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        {{-- Visiteur Section --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Visiteur
                            </h4>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nom</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedSubmission->visiteur_prenom }} {{ $selectedSubmission->visiteur_nom }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        <a href="mailto:{{ $selectedSubmission->visiteur_email }}" class="text-blue-600 hover:underline">
                                            {{ $selectedSubmission->visiteur_email }}
                                        </a>
                                    </p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Téléphone</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedSubmission->visiteur_telephone ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Message Section --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                                Message
                            </h4>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $selectedSubmission->visiteur_message }}</p>
                            </div>
                        </div>

                        {{-- Etablissement Section --}}
                        @if($selectedSubmission->etablissement_nom || $selectedSubmission->etablissement_apidae_id)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Établissement concerné
                            </h4>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nom</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedSubmission->etablissement_nom ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ID Apidae</p>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedSubmission->etablissement_apidae_id ?? '-' }}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Email établissement</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        @if($selectedSubmission->etablissement_email)
                                            <a href="mailto:{{ $selectedSubmission->etablissement_email }}" class="text-blue-600 hover:underline">
                                                {{ $selectedSubmission->etablissement_email }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Metadata Section --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Informations techniques
                            </h4>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <p class="font-medium text-gray-500 dark:text-gray-400">Date de soumission</p>
                                    <p class="text-gray-900 dark:text-white">{{ $selectedSubmission->date_soumission?->format('d/m/Y H:i') ?? $selectedSubmission->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500 dark:text-gray-400">Form ID</p>
                                    <p class="text-gray-900 dark:text-white">{{ $selectedSubmission->form_id }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500 dark:text-gray-400">IP Visiteur</p>
                                    <p class="text-gray-900 dark:text-white">{{ $selectedSubmission->ip_visiteur ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500 dark:text-gray-400">Page source</p>
                                    <p class="text-gray-900 dark:text-white truncate" title="{{ $selectedSubmission->url_page }}">
                                        @if($selectedSubmission->url_page)
                                            <a href="{{ $selectedSubmission->url_page }}" target="_blank" class="text-blue-600 hover:underline">
                                                {{ Str::limit($selectedSubmission->url_page, 40) }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="markAsUnread({{ $selectedSubmission->id }})"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Marquer comme non lu
                        </button>
                        <button
                            wire:click="deleteSubmission({{ $selectedSubmission->id }})"
                            wire:confirm="Êtes-vous sûr de vouloir supprimer cette soumission ?"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                        >
                            Supprimer
                        </button>
                        <button
                            wire:click="closeDetailModal"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
