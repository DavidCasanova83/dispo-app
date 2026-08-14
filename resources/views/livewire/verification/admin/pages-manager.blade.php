<div class="flex h-full w-full flex-1 flex-col gap-4">
    {{-- ═══ EN-TÊTE ═══ --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pages à vérifier</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Créez les pages à faire relire par vos collègues et attribuez-les.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                </svg>
                Ajouter une page
            </button>

            {{-- Menu des actions rares (sitemap) : sorties de la barre principale --}}
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.outside="open = false"
                    title="Autres actions"
                    class="inline-flex items-center justify-center w-9 h-9 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM18 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity
                    class="absolute right-0 z-40 mt-1 w-64 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1">
                    <button type="button" wire:click="scanSitemap" @click="open = false"
                        wire:loading.attr="disabled" wire:target="scanSitemap"
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="scanSitemap">Scanner le sitemap</span>
                        <span wire:loading wire:target="scanSitemap">Scan en cours…</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Importe les nouvelles URLs du site</span>
                    </button>
                    <button type="button" wire:click="openResetModal" @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Réinitialiser le suivi sitemap
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Aucune page n'est supprimée</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Résultat de scan --}}
    @if ($scanResult)
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 border border-emerald-200 dark:border-emerald-800 flex items-start gap-3">
            <div class="flex-1 text-sm text-emerald-900 dark:text-emerald-200">
                <p class="font-semibold">Scan terminé</p>
                <p class="mt-0.5">
                    <strong>{{ $scanResult['created'] }}</strong> ajoutée(s) ·
                    <strong>{{ $scanResult['updated'] }}</strong> confirmée(s) ·
                    <strong>{{ $scanResult['marked_obsolete'] }}</strong> hors sitemap ·
                    <strong>{{ $scanResult['total_in_sitemap'] }}</strong> au total dans le sitemap
                </p>
            </div>
            <button type="button" wire:click="dismissScanResult" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">×</button>
        </div>
    @endif
    @if ($scanError)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800 flex items-start gap-3">
            <div class="flex-1 text-sm text-red-900 dark:text-red-200">
                <p class="font-semibold">Erreur de scan</p>
                <p class="mt-0.5">{{ $scanError }}</p>
            </div>
            <button type="button" wire:click="dismissScanResult" class="text-red-600 hover:text-red-800 dark:text-red-400">×</button>
        </div>
    @endif

    {{-- ═══ TUILES = FILTRES RAPIDES ═══ --}}
    @php $activeTile = $this->activeQuickFilter(); @endphp
    <div class="grid gap-2 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
        <x-verif.stat-filter label="Toutes" :count="$stats['total']" :active="$activeTile === 'all'"
            wire:click="applyQuickFilter('all')" />
        <x-verif.stat-filter label="À vérifier" tone="warn" :count="$stats['pending']" :active="$activeTile === 'pending'"
            wire:click="applyQuickFilter('pending')" />
        <x-verif.stat-filter label="À corriger" tone="danger" :count="$stats['needs_fix']" :active="$activeTile === 'needs_fix'"
            wire:click="applyQuickFilter('needs_fix')" />
        <x-verif.stat-filter label="À clôturer" tone="accent" :count="$stats['awaiting_validation']" :active="$activeTile === 'awaiting_validation'"
            wire:click="applyQuickFilter('awaiting_validation')" />
        <x-verif.stat-filter label="En retard" tone="danger" alert :count="$stats['overdue']" :active="$activeTile === 'overdue'"
            wire:click="applyQuickFilter('overdue')" />
        <x-verif.stat-filter label="Sans relecteur" tone="danger" alert :count="$stats['without_assignee']" :active="$activeTile === 'without_assignee'"
            wire:click="applyQuickFilter('without_assignee')" />
    </div>

    {{-- ═══ FILTRES SECONDAIRES ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        <div class="relative flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une URL, un thème…"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
            </svg>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="filterPriority"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="all">Toutes priorités</option>
                <option value="high">Priorité haute</option>
                <option value="medium">Priorité moyenne</option>
                <option value="low">Priorité basse</option>
            </select>
            <select wire:model.live="filterCategory"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="all">Toutes catégories</option>
                <option value="none">Sans catégorie</option>
                @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterReviewer"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white"
                title="N'afficher que les pages assignées à ce relecteur">
                <option value="">Tous les relecteurs</option>
                @foreach ($availableUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterSitemap"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white"
                title="Présence dans le dernier scan du sitemap">
                <option value="all">Sitemap : tout</option>
                <option value="in">Dans le sitemap ({{ $stats['in_sitemap'] }})</option>
                <option value="out">Hors sitemap ({{ $stats['orphan'] }})</option>
            </select>
            <select wire:model.live="perPage"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                @foreach (\App\Livewire\Verification\Admin\PagesManager::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }} / page</option>
                @endforeach
            </select>
            @if ($this->hasActiveFilters())
                <button type="button" wire:click="resetFilters"
                    class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">
                    Réinitialiser
                </button>
            @endif
        </div>
    </div>

    {{-- ═══ BARRE D'ACTIONS GROUPÉES ═══ --}}
    @if (! empty($selectedPageIds))
        <div class="sticky top-0 z-30 rounded-lg border border-gray-900 dark:border-white bg-white dark:bg-gray-800 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-sm">
            <div class="text-sm text-gray-900 dark:text-white">
                <strong>{{ count($selectedPageIds) }}</strong> page{{ count($selectedPageIds) > 1 ? 's' : '' }} sélectionnée{{ count($selectedPageIds) > 1 ? 's' : '' }}
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="openBulkAssignModal"
                    class="px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                    Assigner des relecteurs
                </button>
                <button type="button" wire:click="openBulkPriorityModal"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Changer la priorité
                </button>
                <button type="button" wire:click="clearSelection"
                    class="px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    Annuler
                </button>
            </div>
        </div>
    @endif

    {{-- ═══ TABLEAU ═══ --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $idsOnPage = $pages->pluck('id')->all();
                    $allOnPageSelected = ! empty($idsOnPage) && empty(array_diff($idsOnPage, $selectedPageIds));
                @endphp
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-2 w-8">
                            <input type="checkbox"
                                @checked($allOnPageSelected)
                                wire:click="toggleSelectAll($event.target.checked, {{ json_encode($idsOnPage) }})"
                                class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white"
                                title="Sélectionner toutes les pages affichées">
                        </th>
                        <x-verif.sortable-th field="title" :active="$this->sortStateFor('title')">Titre</x-verif.sortable-th>
                        <x-verif.sortable-th field="priority" :active="$this->sortStateFor('priority')">Prio</x-verif.sortable-th>
                        <x-verif.sortable-th field="deadline" :active="$this->sortStateFor('deadline')">Deadline</x-verif.sortable-th>
                        <x-verif.sortable-th field="status" :active="$this->sortStateFor('status')">Statut</x-verif.sortable-th>
                        <x-verif.sortable-th field="assignees" :active="$this->sortStateFor('assignees')">Relecteurs</x-verif.sortable-th>
                        <th class="px-3 py-2 w-10"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($pages as $page)
                        <tr wire:key="page-{{ $page->id }}"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $page->assignees->isEmpty() ? 'bg-red-50/40 dark:bg-red-900/10' : '' }}">
                            <td class="px-3 py-2 align-top">
                                <input type="checkbox" value="{{ $page->id }}" wire:model.live="selectedPageIds"
                                    class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            </td>

                            {{-- Titre : URL, thème, catégorie et langues regroupés ici --}}
                            <td class="px-3 py-2 align-top max-w-md">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $page->title }}</div>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5">
                                    <a href="{{ $page->url }}" target="_blank" rel="noopener"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:underline truncate max-w-[18rem]">
                                        {{ \Illuminate\Support\Str::after($page->url, '://') }}
                                    </a>
                                    <span class="text-xs text-gray-400 dark:text-gray-600" title="Versions traduites renseignées">
                                        <span class="text-gray-700 dark:text-gray-300">FR</span>{{ $page->url_en ? ' · EN' : '' }}{{ $page->url_it ? ' · IT' : '' }}
                                    </span>
                                    @if ($page->category)
                                        <x-verif.status-badge size="xs" tone="neutral" :label="$page->categoryLabel()" />
                                    @endif
                                    @if ($page->theme)
                                        <span class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-[10rem]">{{ $page->theme }}</span>
                                    @endif
                                    @if (! $page->is_in_sitemap && $page->last_seen_in_sitemap_at)
                                        <x-verif.status-badge size="xs" tone="warn" label="Hors sitemap"
                                            title="Cette page n'apparaît plus dans le dernier scan du sitemap" />
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-2 align-top">
                                <x-verif.priority-dot :priority="$page->priority" class="mt-1.5" />
                            </td>

                            <td class="px-3 py-2 align-top">
                                <x-verif.deadline-badge :page="$page" />
                            </td>

                            <td class="px-3 py-2 align-top">
                                <x-verif.status-badge :tone="$page->statusTone()" :label="$page->statusLabel()" />
                            </td>

                            {{-- Relecteurs : la cellule entière ouvre la modale d'assignation --}}
                            <td class="px-3 py-2 align-top">
                                <button type="button" wire:click="openAssignModal({{ $page->id }})"
                                    class="text-left group" title="Modifier les relecteurs">
                                    @if ($page->assignees->isEmpty())
                                        <x-verif.status-badge tone="danger" label="Aucun relecteur" />
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($page->assignees as $assignee)
                                                @php $isReleased = $assignee->pivot->released_at !== null; @endphp
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] group-hover:ring-1 group-hover:ring-gray-300 dark:group-hover:ring-gray-600
                                                    {{ $isReleased
                                                        ? 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300'
                                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' }}"
                                                    title="{{ $isReleased
                                                        ? 'Page libérée — visible sur le dashboard de ' . $assignee->name
                                                        : $assignee->name . ' recevra cette page dès qu\'une place se libère (distribution chaque nuit)' }}">
                                                    {{ $assignee->name }}@if (! $isReleased)<span class="opacity-70">⏳</span>@endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </button>
                            </td>

                            {{-- Actions regroupées dans un menu : une seule cible par ligne --}}
                            <td class="px-3 py-2 align-top text-right">
                                <div x-data="{ open: false }" class="relative inline-block">
                                    <button type="button" @click="open = !open" @click.outside="open = false"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700"
                                        title="Actions">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.opacity
                                        class="absolute right-0 z-40 mt-1 w-52 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1 text-left">
                                        @if ($page->queued_count > 0)
                                            <button type="button" wire:click="openReleaseModal({{ $page->id }})" @click="open = false"
                                                class="w-full text-left px-3 py-2 text-sm text-amber-700 dark:text-amber-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                Libérer maintenant
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $page->queued_count }} relecteur(s) en attente</span>
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openAssignModal({{ $page->id }})" @click="open = false"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Assigner des relecteurs
                                        </button>
                                        <button type="button" wire:click="openEditModal({{ $page->id }})" @click="open = false"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Éditer la page
                                        </button>
                                        <a href="{{ $page->url }}" target="_blank" rel="noopener" @click="open = false"
                                            class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Ouvrir la page ↗
                                        </a>
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                                        <button type="button" wire:click="openDeleteModal({{ $page->id }})" @click="open = false"
                                            class="w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($this->hasActiveFilters())
                                    Aucune page ne correspond à ces filtres.
                                    <button type="button" wire:click="resetFilters" class="ml-1 underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">
                                        Réinitialiser
                                    </button>
                                @else
                                    Aucune page pour le moment. Utilisez « Ajouter une page », ou « Scanner le sitemap » dans le menu ⋯ pour importer les URLs du site.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $pages->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="form-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeFormModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $editingId ? 'Éditer la page' : 'Nouvelle page à vérifier' }}
                    </h2>
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre *</label>
                            <input type="text" wire:model="title"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🇫🇷 URL FR *</label>
                            <input type="url" wire:model="url" placeholder="https://www.verdontourisme.com/…"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🇬🇧 URL EN (optionnel)</label>
                                <input type="url" wire:model="urlEn" placeholder="https://www.verdontourisme.com/en/…"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                @error('urlEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🇮🇹 URL IT (optionnel)</label>
                                <input type="url" wire:model="urlIt" placeholder="https://www.verdontourisme.com/it/…"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                @error('urlIt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">
                            💡 Renseignez ces URLs pour permettre aux relecteurs de vérifier les versions traduites.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Thème (texte libre)</label>
                                <input type="text" wire:model="theme" placeholder="Ex : Activités outdoor…"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie (optionnel)</label>
                                <select wire:model="category"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    <option value="">— Aucune —</option>
                                    @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorité</label>
                                <select wire:model="priority"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    <option value="high">🔴 Haute</option>
                                    <option value="medium">🟠 Moyenne</option>
                                    <option value="low">🟢 Basse</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                                <input type="date" wire:model="deadline"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Relecteurs assignés</label>
                            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-48 overflow-y-auto bg-white dark:bg-gray-700">
                                @forelse ($availableUsers as $user)
                                    <label class="flex items-center gap-2 py-1 cursor-pointer">
                                        <input type="checkbox" value="{{ $user->id }}" wire:model="assigneeIds"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                        <span class="text-xs text-gray-500">({{ $user->email }})</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500 italic">Aucun utilisateur approuvé.</p>
                                @endforelse
                            </div>
                            @error('assigneeIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeFormModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                                {{ $editingId ? 'Mettre à jour' : 'Créer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Assign modal --}}
    @if ($showAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="assign-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeAssignModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Assigner des relecteurs</h2>
                    <form wire:submit.prevent="saveAssignment" class="space-y-4">
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-72 overflow-y-auto bg-white dark:bg-gray-700">
                            @forelse ($availableUsers as $user)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" value="{{ $user->id }}" wire:model.live="quickAssigneeIds"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500">({{ $user->email }})</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic">Aucun utilisateur approuvé.</p>
                            @endforelse
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeAssignModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk assign modal --}}
    @if ($showBulkAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="bulk-assign-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeBulkAssignModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        Assigner à {{ count($selectedPageIds) }} page{{ count($selectedPageIds) > 1 ? 's' : '' }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Les relecteurs cochés seront <strong>ajoutés</strong> aux relecteurs déjà assignés (rien n'est écrasé). Les nouvelles assignations seront distribuées la nuit prochaine, au fil des places disponibles, ou immédiatement via « Libérer maintenant ».
                    </p>
                    <form wire:submit.prevent="saveBulkAssignment" class="space-y-4">
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-72 overflow-y-auto bg-white dark:bg-gray-700">
                            @forelse ($availableUsers as $user)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" value="{{ $user->id }}" wire:model.live="bulkAssigneeIds"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500">({{ $user->email }})</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic">Aucun utilisateur approuvé.</p>
                            @endforelse
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeBulkAssignModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="submit"
                                @disabled(empty($bulkAssigneeIds))
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg">
                                Assigner ({{ count($bulkAssigneeIds) }})
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk priority modal --}}
    @if ($showBulkPriorityModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="bulk-priority-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeBulkPriorityModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        Changer la priorité de {{ count($selectedPageIds) }} page{{ count($selectedPageIds) > 1 ? 's' : '' }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        La nouvelle priorité écrasera celle des pages sélectionnées.
                    </p>
                    <form wire:submit.prevent="saveBulkPriority" class="space-y-4">
                        <div class="space-y-2">
                            @foreach (['high' => 'Priorité haute', 'medium' => 'Priorité moyenne', 'low' => 'Priorité basse'] as $code => $label)
                                <label class="flex items-center gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                    <input type="radio" wire:model.live="bulkPriority" value="{{ $code }}"
                                        class="text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                    <x-verif.priority-dot :priority="$code" />
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeBulkPriorityModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                                Appliquer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Release now modal --}}
    @if ($showReleaseModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="release-now-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeReleaseModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Libérer cette page maintenant ?</h2>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <p>« <strong>{{ $releasingPageTitle }}</strong> » sera <strong>immédiatement visible</strong> sur le dashboard de
                            @if ($releasingQueuedCount === 1) <strong>1 relecteur</strong> en attente.
                            @else <strong>{{ $releasingQueuedCount }} relecteurs</strong> en attente.
                            @endif
                        </p>
                        <p class="rounded bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-2 text-amber-800 dark:text-amber-200">
                            Attention : cette libération s'<strong>ajoute</strong> aux pages déjà actives chez ces relecteurs cette semaine. Ils peuvent donc voir plus de 2 pages d'un coup.
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeReleaseModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                            Annuler
                        </button>
                        <button type="button" wire:click="releaseNow"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg">
                            Libérer maintenant
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reset sitemap modal --}}
    @if ($showResetModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="reset-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeResetModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Réinitialiser l'état du sitemap ?</h2>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <p>Cette action remet à zéro les indicateurs <em>« dans le sitemap »</em> et <em>« dernière apparition »</em> sur toutes les pages.</p>
                        <p class="rounded bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-2 text-emerald-800 dark:text-emerald-200">
                            ✅ <strong>Aucune page n'est supprimée.</strong> Aucune assignation, aucune relecture n'est touchée.
                        </p>
                        <p>Lancez un nouveau scan ensuite pour rafraîchir l'état réel.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeResetModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                            Annuler
                        </button>
                        <button type="button" wire:click="resetSitemapTracking"
                            wire:loading.attr="disabled"
                            wire:target="resetSitemapTracking"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 disabled:opacity-50 rounded-lg">
                            <svg class="w-4 h-4 animate-spin" wire:loading wire:target="resetSitemapTracking" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="delete-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeDeleteModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Retirer cette page ?</h2>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <p>La page disparaîtra de la liste et des dashboards des relecteurs.</p>
                        <p class="rounded bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-2 text-green-800 dark:text-green-200">
                            Ses relectures et ses assignations sont <strong>conservées</strong>. En cas d'erreur :
                            <code class="text-xs">php artisan verification:restore-page {id}</code>
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                            Annuler
                        </button>
                        <button type="button" wire:click="delete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
