<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Commandes</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gérez les commandes d'images</p>
        </div>
        <button wire:click="exportCsv" class="px-4 py-2 bg-[#3E9B90] text-white rounded-lg hover:bg-[#357f76]">
            Exporter CSV
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">En attente</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">En cours</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['processing'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Terminées</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <div class="flex gap-4 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500">
            <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="all">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="processing">En cours</option>
                <option value="completed">Terminée</option>
                <option value="cancelled">Annulée</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">N° Commande</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Date</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Client</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Utilisateur</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Type</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Langue</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Statut</th>
                        <th class="text-left p-3 text-gray-900 dark:text-white font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="p-3 font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-3 text-gray-900 dark:text-white">{{ $order->full_name }}</td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">{{ $order->user->name ?? 'N/A' }}</td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($order->customer_type) }}</td>
                            <td class="p-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($order->language) }}</td>
                            <td class="p-3">
                                <select wire:change="updateStatus({{ $order->id }}, $event.target.value)" class="text-sm rounded px-2 py-1 {{ $order->status_color }}">
                                    <option value="pending" @selected($order->status === 'pending')>En attente</option>
                                    <option value="processing" @selected($order->status === 'processing')>En cours</option>
                                    <option value="completed" @selected($order->status === 'completed')>Terminée</option>
                                    <option value="cancelled" @selected($order->status === 'cancelled')>Annulée</option>
                                </select>
                            </td>
                            <td class="p-3">
                                <button wire:click="openDetailModal({{ $order->id }})" class="text-blue-600 hover:underline">Détails</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-8 text-center text-gray-600 dark:text-gray-400">Aucune commande trouvée</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    </div>

    @if($showDetailModal && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="closeDetailModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Commande {{ $selectedOrder->order_number }}</h3>

                    <div class="space-y-4 text-gray-800 dark:text-gray-200">
                        <div class="grid grid-cols-2 gap-4">
                            <div><strong class="text-gray-900 dark:text-white">Client:</strong> {{ $selectedOrder->civility }} {{ $selectedOrder->full_name }}</div>
                            <div><strong class="text-gray-900 dark:text-white">Email:</strong> {{ $selectedOrder->email }}</div>
                            <div><strong class="text-gray-900 dark:text-white">Téléphone:</strong> {{ $selectedOrder->full_phone ?? 'N/A' }}</div>
                            <div><strong class="text-gray-900 dark:text-white">Type:</strong> {{ ucfirst($selectedOrder->customer_type) }}</div>
                            @if($selectedOrder->company)
                                <div><strong class="text-gray-900 dark:text-white">Société:</strong> {{ $selectedOrder->company }}</div>
                            @endif
                            <div><strong class="text-gray-900 dark:text-white">Langue:</strong> {{ ucfirst($selectedOrder->language) }}</div>
                        </div>

                        <div><strong class="text-gray-900 dark:text-white">Adresse:</strong><br>{{ $selectedOrder->full_address }}</div>

                        <div>
                            <strong class="text-gray-900 dark:text-white">Images commandées:</strong>
                            <ul class="mt-2 space-y-2">
                                @foreach($selectedOrder->items as $item)
                                    <li class="flex items-center gap-3">
                                        <img src="{{ asset('storage/' . $item->image->thumbnail_path) }}" class="w-12 h-12 object-cover rounded">
                                        <span>{{ $item->image->title ?? $item->image->name }} (x{{ $item->quantity }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if($selectedOrder->customer_notes)
                            <div><strong class="text-gray-900 dark:text-white">Notes client:</strong><br>{{ $selectedOrder->customer_notes }}</div>
                        @endif

                        <div>
                            <label class="block font-bold mb-2 text-gray-900 dark:text-white">Notes admin:</label>
                            <textarea wire:model="adminNotes" rows="3" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                            <button wire:click="saveAdminNotes" class="mt-2 px-4 py-2 bg-[#3E9B90] text-white rounded hover:bg-[#357f76]">Enregistrer</button>
                        </div>
                    </div>

                    <button wire:click="closeDetailModal" class="mt-4 px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Fermer</button>
                </div>
            </div>
        </div>
    @endif
</div>
