<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Header -->
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('qualification.city.dashboard', $city) }}"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                ← Retour
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Données de Qualification</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $cityName }}</p>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Total</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <div class="text-xl font-bold text-green-600">{{ $stats['completed'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Complètes</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <div class="text-xl font-bold text-orange-600">{{ $stats['incomplete'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Incomplètes</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <div class="text-xl font-bold text-blue-600">{{ $stats['today'] }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Aujourd'hui</div>
        </div>
    </div>

    <!-- Messages flash -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
                <input type="text" wire:model.live="search"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="Email...">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                <select wire:model.live="completedFilter"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Tous</option>
                    <option value="1">Complète</option>
                    <option value="0">Incomplète</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
        </div>

        <div class="mt-4">
            <button wire:click="clearFilters"
                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors">
                Réinitialiser les filtres
            </button>
        </div>
    </div>

    <!-- Liste des qualifications -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pays</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Profil</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($qualifications as $qualification)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $qualification->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $qualification->form_data['email'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $qualification->form_data['country'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $qualification->form_data['profile'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($qualification->completed)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Complète
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        Incomplète
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                @if ($editingId === $qualification->id)
                                    <button wire:click="save"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        Sauvegarder
                                    </button>
                                    <button wire:click="cancelEdit"
                                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                                        Annuler
                                    </button>
                                @else
                                    <button wire:click="edit({{ $qualification->id }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Éditer
                                    </button>
                                    <button wire:click="delete({{ $qualification->id }})"
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?')"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Supprimer
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($editingId === $qualification->id)
                            <tr>
                                <td colspan="6" class="px-6 py-4 bg-gray-50 dark:bg-gray-900">
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Édition des données</h3>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                                <input type="email" wire:model="editingData.email"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pays</label>
                                                <input type="text" wire:model="editingData.country"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Département</label>
                                                <input type="text" wire:model="editingData.department"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profil</label>
                                                <input type="text" wire:model="editingData.profile"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Autres demandes</label>
                                                <textarea wire:model="editingData.otherRequest" rows="3"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucune donnée trouvée
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $qualifications->links() }}
        </div>
    </div>
</div>
