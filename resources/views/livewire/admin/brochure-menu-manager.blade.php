<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Menu Brochures OTI-VT</h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Configurez les liens du menu latéral de la page brochures
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-6 shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="flex-1 text-white font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Bouton Ajouter --}}
        <div class="mb-6 flex justify-between items-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $menuItems->count() }} élément(s) de premier niveau
            </p>
            <button wire:click="createItem"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-[#3E9B90] text-white hover:bg-[#357f76] transition-colors shadow-md cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Ajouter un lien
            </button>
        </div>

        {{-- Liste des items --}}
        <div class="space-y-3">
            @forelse ($menuItems as $item)
                {{-- Item parent --}}
                <div class="bg-white dark:bg-zinc-800 shadow-md dark:shadow-zinc-950/50 rounded-lg overflow-hidden border border-gray-200 dark:border-zinc-700">
                    <div class="p-4 flex items-center gap-3">
                        {{-- Flèches d'ordre --}}
                        <div class="flex flex-col gap-0.5">
                            <button wire:click="moveUp({{ $item->id }})"
                                class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer disabled:opacity-30"
                                @if ($loop->first) disabled @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button wire:click="moveDown({{ $item->id }})"
                                class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer disabled:opacity-30"
                                @if ($loop->last) disabled @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Indicateur actif --}}
                        <button wire:click="toggleActive({{ $item->id }})" class="cursor-pointer" title="{{ $item->is_active ? 'Désactiver' : 'Activer' }}">
                            <span class="w-3 h-3 rounded-full inline-block {{ $item->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600' }}"></span>
                        </button>

                        {{-- Infos --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 {{ !$item->is_active ? 'opacity-50' : '' }}">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $item->title }}
                                </p>
                                @if ($item->auth_only)
                                    <span class="flex-shrink-0 px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">Connectés</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate {{ !$item->is_active ? 'opacity-50' : '' }}">
                                {{ $item->url }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1">
                            <button wire:click="createSubItem({{ $item->id }})"
                                class="p-2 text-[#3E9B90] hover:bg-[#3E9B90]/10 rounded-lg transition-colors cursor-pointer"
                                title="Ajouter un sous-lien">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                            <button wire:click="editItem({{ $item->id }})"
                                class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors cursor-pointer"
                                title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $item->id }})"
                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer"
                                title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Sous-items --}}
                    @if ($item->children->count() > 0)
                        <div class="border-t border-gray-200 dark:border-zinc-700">
                            @foreach ($item->children as $child)
                                <div class="pl-12 pr-4 py-3 flex items-center gap-3 border-b border-gray-100 dark:border-zinc-700/50 last:border-b-0 bg-gray-50 dark:bg-zinc-800/50">
                                    {{-- Flèches d'ordre --}}
                                    <div class="flex flex-col gap-0.5">
                                        <button wire:click="moveUp({{ $child->id }})"
                                            class="p-0.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer disabled:opacity-30"
                                            @if ($loop->first) disabled @endif>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="moveDown({{ $child->id }})"
                                            class="p-0.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer disabled:opacity-30"
                                            @if ($loop->last) disabled @endif>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Indicateur actif --}}
                                    <button wire:click="toggleActive({{ $child->id }})" class="cursor-pointer" title="{{ $child->is_active ? 'Désactiver' : 'Activer' }}">
                                        <span class="w-2.5 h-2.5 rounded-full inline-block {{ $child->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600' }}"></span>
                                    </button>

                                    {{-- Infos --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 {{ !$child->is_active ? 'opacity-50' : '' }}">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                                {{ $child->title }}
                                            </p>
                                            @if ($child->auth_only)
                                                <span class="flex-shrink-0 px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">Connectés</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate {{ !$child->is_active ? 'opacity-50' : '' }}">
                                            {{ $child->url }}
                                        </p>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1">
                                        <button wire:click="editItem({{ $child->id }})"
                                            class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors cursor-pointer"
                                            title="Modifier">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $child->id }})"
                                            class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer"
                                            title="Supprimer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white dark:bg-zinc-800 shadow-md dark:shadow-zinc-950/50 rounded-lg p-12 text-center border border-gray-200 dark:border-zinc-700">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Aucun élément de menu</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Ajoutez des liens pour construire le menu latéral</p>
                    <button wire:click="createItem"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-[#3E9B90] text-white hover:bg-[#357f76] transition-colors shadow-md cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Ajouter le premier lien
                    </button>
                </div>
            @endforelse
        </div>

        {{-- Aperçu --}}
        @if ($menuItems->count() > 0)
            <div class="mt-8 bg-white dark:bg-zinc-800 shadow-md dark:shadow-zinc-950/50 rounded-lg overflow-hidden border border-gray-200 dark:border-zinc-700">
                <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Aperçu du menu</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tel qu'il apparaîtra sur la page brochures</p>
                </div>
                <div class="p-4">
                    <nav class="space-y-1 max-w-xs">
                        @foreach ($menuItems as $item)
                            @if ($item->is_active)
                                <div>
                                    <a href="#" onclick="event.preventDefault()"
                                        class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                                        <svg class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        <span class="truncate">{{ $item->title }}</span>
                                    </a>
                                    @foreach ($item->children as $child)
                                        @if ($child->is_active)
                                            <a href="#" onclick="event.preventDefault()"
                                                class="block ml-6 px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors border-l-2 border-gray-200 dark:border-zinc-600 pl-4">
                                                {{ $child->title }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </nav>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Ajout/Édition --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl dark:shadow-zinc-950/50 transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-zinc-700">
                    <form wire:submit="saveItem">
                        <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                @if ($editingId)
                                    Modifier l'élément
                                @elseif ($editingParentId)
                                    Ajouter un sous-lien
                                @else
                                    Ajouter un lien
                                @endif
                            </h3>

                            @if ($editingParentId && !$editingId)
                                @php $parentItem = \App\Models\BrochureMenuItem::find($editingParentId); @endphp
                                @if ($parentItem)
                                    <div class="mb-4 p-2 bg-gray-100 dark:bg-zinc-700 rounded-lg text-sm text-gray-700 dark:text-gray-300">
                                        Sous-lien de : <strong class="text-gray-900 dark:text-white">{{ $parentItem->title }}</strong>
                                    </div>
                                @endif
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label for="itemTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Titre
                                    </label>
                                    <input type="text" id="itemTitle" wire:model="itemTitle"
                                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm focus:border-[#3E9B90] focus:ring-[#3E9B90]"
                                        placeholder="Ex: Cartes et plans">
                                    @error('itemTitle')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="itemUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        URL
                                    </label>
                                    <input type="text" id="itemUrl" wire:model="itemUrl"
                                        class="w-full rounded-md border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm focus:border-[#3E9B90] focus:ring-[#3E9B90]"
                                        placeholder="Ex: /brochures-oti-vt/cartes-plans">
                                    @error('itemUrl')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="itemIsActive" wire:model="itemIsActive"
                                        class="rounded border-gray-300 dark:border-zinc-600 text-[#3E9B90] focus:ring-[#3E9B90]">
                                    <label for="itemIsActive" class="text-sm text-gray-700 dark:text-gray-300">
                                        Actif (visible dans le menu)
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="itemAuthOnly" wire:model="itemAuthOnly"
                                        class="rounded border-gray-300 dark:border-zinc-600 text-amber-500 focus:ring-amber-500">
                                    <label for="itemAuthOnly" class="text-sm text-gray-700 dark:text-gray-300">
                                        Réservé aux utilisateurs connectés
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-zinc-700">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#3E9B90] text-base font-medium text-white hover:bg-[#357f76] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3E9B90] sm:ml-3 sm:w-auto sm:text-sm cursor-pointer">
                                {{ $editingId ? 'Enregistrer' : 'Ajouter' }}
                            </button>
                            <button type="button" wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Suppression --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" wire:click="cancelDelete"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl dark:shadow-zinc-950/50 transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200 dark:border-zinc-700">
                    <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Supprimer l'élément
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Voulez-vous vraiment supprimer "<strong class="text-gray-900 dark:text-white">{{ $deletingTitle }}</strong>" ?
                                        Cette action supprimera aussi tous ses sous-liens.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-zinc-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-zinc-700">
                        <button wire:click="deleteItem"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer">
                            Supprimer
                        </button>
                        <button wire:click="cancelDelete"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-600 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
