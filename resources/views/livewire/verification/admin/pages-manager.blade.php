<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pages à vérifier</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Créez les pages à faire relire par vos collègues et attribuez-les.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button
                wire:click="scanSitemap"
                wire:loading.attr="disabled"
                wire:target="scanSitemap"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-lg transition-colors">
                <svg class="w-5 h-5" wire:loading.remove wire:target="scanSitemap" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg class="w-5 h-5 animate-spin" wire:loading wire:target="scanSitemap" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="scanSitemap">Scanner le sitemap</span>
                <span wire:loading wire:target="scanSitemap">Scan en cours…</span>
            </button>
            <button
                wire:click="openResetModal"
                title="Remet à zéro l'état du sitemap (aucune page supprimée)"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30 hover:bg-amber-200 dark:hover:bg-amber-900/50 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                </svg>
                Réinitialiser
            </button>
            <button
                wire:click="openHardDeleteModal"
                title="Supprime DÉFINITIVEMENT toutes les pages, assignations et relectures"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"></path>
                </svg>
                Tout supprimer
            </button>
            <button
                wire:click="openCreateModal"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Ajouter une page
            </button>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Scan result banner --}}
    @if ($scanResult)
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-4 border border-emerald-200 dark:border-emerald-800 flex items-start gap-3">
            <div class="flex-1 text-sm text-emerald-900 dark:text-emerald-200">
                <p class="font-semibold">✅ Scan terminé</p>
                <ul class="mt-1 space-y-0.5">
                    <li>📥 <strong>{{ $scanResult['created'] }}</strong> nouvelle(s) page(s) ajoutée(s)</li>
                    <li>🔄 <strong>{{ $scanResult['updated'] }}</strong> page(s) déjà connue(s) et confirmée(s) dans le sitemap</li>
                    <li>👻 <strong>{{ $scanResult['marked_obsolete'] }}</strong> page(s) absente(s) du sitemap (marquée(s) hors-sitemap)</li>
                    <li>📊 Total dans le sitemap : <strong>{{ $scanResult['total_in_sitemap'] }}</strong></li>
                </ul>
            </div>
            <button type="button" wire:click="dismissScanResult" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">×</button>
        </div>
    @endif
    @if ($scanError)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800 flex items-start gap-3">
            <div class="flex-1 text-sm text-red-900 dark:text-red-200">
                <p class="font-semibold">❌ Erreur de scan</p>
                <p class="mt-1">{{ $scanError }}</p>
            </div>
            <button type="button" wire:click="dismissScanResult" class="text-red-600 hover:text-red-800 dark:text-red-400">×</button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-4 grid-cols-2 md:grid-cols-4 lg:grid-cols-8">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">À vérifier</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">À corriger</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['needs_fix'] }}</p>
        </div>
        <div class="rounded-xl border {{ $stats['awaiting_validation'] > 0 ? 'border-purple-300 dark:border-purple-700 bg-purple-50 dark:bg-purple-900/20' : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800' }} p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">À clôturer</p>
            <p class="text-2xl font-bold {{ $stats['awaiting_validation'] > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400' }}">{{ $stats['awaiting_validation'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Validées</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['validated'] }}</p>
        </div>
        <div class="rounded-xl border-2 {{ $stats['without_assignee'] > 0 ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20' : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800' }} p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Sans relecteur</p>
            <p class="text-2xl font-bold {{ $stats['without_assignee'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                {{ $stats['without_assignee'] }}
            </p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Dans sitemap</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['in_sitemap'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Hors sitemap</p>
            <p class="text-2xl font-bold {{ $stats['orphan'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-400' }}">
                {{ $stats['orphan'] }}
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher (titre, URL, thème)…"
            class="lg:col-span-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
        <select wire:model.live="filterStatus"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Tous les statuts</option>
            <option value="pending">À vérifier</option>
            <option value="in_progress">En cours</option>
            <option value="needs_fix">À corriger</option>
            <option value="awaiting_validation">En attente de validation</option>
            <option value="validated">Validée</option>
        </select>
        <select wire:model.live="filterPriority"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toutes priorités</option>
            <option value="high">Priorité haute</option>
            <option value="medium">Priorité moyenne</option>
            <option value="low">Priorité basse</option>
        </select>
        <select wire:model.live="filterCategory"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toutes catégories</option>
            <option value="none">Sans catégorie</option>
            @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterAssignment"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toutes pages</option>
            <option value="without">⚠️ Sans relecteur</option>
            <option value="with">Avec relecteur</option>
        </select>
        <select wire:model.live="perPage"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
            title="Nombre de pages par page">
            @foreach (\App\Livewire\Verification\Admin\PagesManager::PER_PAGE_OPTIONS as $opt)
                <option value="{{ $opt }}">{{ $opt }} / page</option>
            @endforeach
            <option value="0">Tout afficher</option>
        </select>
    </div>

    {{-- Bulk action bar (visible si au moins 1 page sélectionnée) --}}
    @if (! empty($selectedPageIds))
        <div class="sticky top-0 z-30 -mx-4 sm:mx-0 rounded-none sm:rounded-lg border border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
            <div class="text-sm text-indigo-900 dark:text-indigo-100">
                <strong>{{ count($selectedPageIds) }}</strong> page{{ count($selectedPageIds) > 1 ? 's' : '' }} sélectionnée{{ count($selectedPageIds) > 1 ? 's' : '' }}
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="openBulkAssignModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
                    Assigner des relecteurs
                </button>
                <button type="button" wire:click="openBulkPriorityModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                    Changer la priorité
                </button>
                <button type="button" wire:click="clearSelection"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Annuler la sélection
                </button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $idsOnPage = $pages->pluck('id')->all();
                    $allOnPageSelected = ! empty($idsOnPage) && empty(array_diff($idsOnPage, $selectedPageIds));
                @endphp
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-3 w-8">
                            <input type="checkbox"
                                @checked($allOnPageSelected)
                                wire:click="toggleSelectAll($event.target.checked, {{ json_encode($idsOnPage) }})"
                                class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white"
                                title="Sélectionner toutes les pages affichées">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Titre / URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Catégorie</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Priorité</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deadline</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Relecteurs</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($pages as $page)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $page->assignees->isEmpty() ? 'bg-red-50/30 dark:bg-red-900/10' : '' }}">
                            <td class="px-3 py-3">
                                <input type="checkbox"
                                    value="{{ $page->id }}"
                                    wire:model.live="selectedPageIds"
                                    class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                    <a href="{{ $page->url }}" target="_blank" rel="noopener" class="hover:underline">{{ $page->url }}</a>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    @if ($page->theme)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">🏷️ {{ $page->theme }}</span>
                                    @endif
                                    {{-- Badges langues disponibles --}}
                                    <span class="inline-flex items-center gap-1 text-xs">
                                        <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" title="Version française (par défaut)">🇫🇷</span>
                                        @if ($page->url_en)
                                            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" title="URL anglaise renseignée">🇬🇧</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-600" title="URL anglaise manquante">🇬🇧</span>
                                        @endif
                                        @if ($page->url_it)
                                            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" title="URL italienne renseignée">🇮🇹</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-600" title="URL italienne manquante">🇮🇹</span>
                                        @endif
                                    </span>
                                    @if (! $page->is_in_sitemap && $page->last_seen_in_sitemap_at)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300" title="Cette page n'apparaît plus dans le dernier scan du sitemap">
                                            👻 Hors sitemap
                                        </span>
                                    @endif
                                    @if ($page->queued_count > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300" title="Cette page est assignée à {{ $page->queued_count }} relecteur(s) mais n'a pas encore été libérée pour eux. Elle le sera automatiquement le dimanche soir, ou immédiatement via le bouton « Libérer »">
                                            En attente · {{ $page->queued_count }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($page->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ $page->categoryLabel() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $page->priorityIcon() }} {{ $page->priorityLabel() }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $page->deadline?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $badge = match ($page->status) {
                                        'pending' => ['À vérifier', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'],
                                        'in_progress' => ['En cours', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
                                        'needs_fix' => ['À corriger', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
                                        'awaiting_validation' => ['En attente de validation', 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'],
                                        'validated' => ['Validée', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                                        default => [$page->status, 'bg-gray-100 text-gray-800'],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($page->assignees->isEmpty())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        ⚠️ Aucun relecteur
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($page->assignees as $assignee)
                                            @php
                                                $isReleased = $assignee->pivot->released_at !== null;
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isReleased
                                                ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' }}"
                                                title="{{ $isReleased ? 'Page libérée — visible sur le dashboard de '.$assignee->name : $assignee->name.' verra cette page le prochain dimanche' }}">
                                                {{ $assignee->name }}@if (! $isReleased) <span class="ml-1 opacity-70">⏳</span>@endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm space-x-2 whitespace-nowrap">
                                @if ($page->queued_count > 0)
                                    <button wire:click="openReleaseModal({{ $page->id }})"
                                        class="text-amber-700 hover:text-amber-900 dark:text-amber-400" title="Libérer immédiatement la page pour les relecteurs en attente">Libérer</button>
                                @endif
                                <button wire:click="openAssignModal({{ $page->id }})"
                                    class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400" title="Assigner des relecteurs">Assigner</button>
                                <button wire:click="openEditModal({{ $page->id }})"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Éditer</button>
                                <button wire:click="openDeleteModal({{ $page->id }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400">Supprimer</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucune page pour le moment. Cliquez sur "Scanner le sitemap" pour importer ou "Ajouter une page" pour saisir manuellement.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $pages->links() }}
        </div>
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
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
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
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
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
                        Les relecteurs cochés seront <strong>ajoutés</strong> aux relecteurs déjà assignés (rien n'est écrasé). Les nouvelles assignations seront libérées dimanche prochain ou via le bouton « Libérer ».
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
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg">
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
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="bulkPriority" value="high"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">🔴 Priorité haute</span>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="bulkPriority" value="medium"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">🟠 Priorité moyenne</span>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                <input type="radio" wire:model.live="bulkPriority" value="low"
                                    class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                <span class="text-sm text-gray-900 dark:text-white">🟢 Priorité basse</span>
                            </label>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeBulkPriorityModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
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
                <div class="relative bg-white dark:bg-gray-800 rounded shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700">
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
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            Annuler
                        </button>
                        <button type="button" wire:click="releaseNow"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded">
                            Libérer maintenant
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Hard delete all modal --}}
    @if ($showHardDeleteModal)
        @php
            $totalPages = \App\Models\VerificationPage::count();
            $totalReviews = \App\Models\VerificationReview::count();
            $totalAssignments = \DB::table('verification_assignments')->count();
            $canDelete = $hardDeleteConfirm1 && $hardDeleteConfirm2 && $totalPages > 0;
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="hard-delete-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/60" wire:click="closeHardDeleteModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6 border-2 border-red-300 dark:border-red-700">
                    <h2 class="text-xl font-bold text-red-700 dark:text-red-300 mb-3 flex items-center gap-2">
                        ⚠️ Suppression définitive de toutes les pages
                    </h2>

                    @if ($totalPages === 0)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Aucune page à supprimer. La table est déjà vide.
                        </p>
                        <div class="flex justify-end">
                            <button type="button" wire:click="closeHardDeleteModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Fermer
                            </button>
                        </div>
                    @else
                        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-4">
                            <p>Cette action supprime <strong>DÉFINITIVEMENT</strong> toutes les pages à vérifier ET leurs relectures associées.</p>

                            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-red-900 dark:text-red-200">
                                <p class="font-semibold mb-2">Cela inclut :</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    <li><strong>{{ $totalPages }}</strong> page{{ $totalPages > 1 ? 's' : '' }}</li>
                                    <li><strong>{{ $totalReviews }}</strong> relecture{{ $totalReviews > 1 ? 's' : '' }} de vos collègues</li>
                                    <li><strong>{{ $totalAssignments }}</strong> assignation{{ $totalAssignments > 1 ? 's' : '' }}</li>
                                </ul>
                            </div>

                            <p class="font-semibold text-red-700 dark:text-red-300">Cette action est IRRÉVERSIBLE.</p>

                            <div class="space-y-2 pt-2">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="hardDeleteConfirm1"
                                        class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="text-sm">Je comprends que <strong>toutes les pages</strong> seront supprimées.</span>
                                </label>
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="hardDeleteConfirm2"
                                        class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="text-sm">Je comprends que <strong>les relectures de mes collègues seront perdues</strong>.</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeHardDeleteModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                                Annuler
                            </button>
                            <button type="button" wire:click="hardDeleteAll"
                                @disabled(! $canDelete)
                                wire:loading.attr="disabled"
                                wire:target="hardDeleteAll"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg">
                                <svg class="w-4 h-4 animate-spin" wire:loading wire:target="hardDeleteAll" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Supprimer définitivement
                            </button>
                        </div>
                    @endif
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
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Supprimer cette page ?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Cette action est définitive. Toutes les relectures associées seront aussi supprimées.
                    </p>
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
