<div class="flex h-full w-full flex-1 flex-col gap-4">
    <x-verif.space-header active="pages" :stage="$filterStage ?: null">
        <x-slot:actions>
            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                + Ajouter une page
            </button>
            {{-- Actions rares --}}
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.outside="open = false" title="Autres actions"
                    class="inline-flex items-center justify-center w-9 h-9 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM18 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span class="sr-only">Autres actions</span>
                </button>
                <div x-show="open" x-cloak x-transition.opacity
                    class="absolute right-0 z-40 mt-1 w-72 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1">
                    <button type="button" wire:click="scanSitemap" @click="open = false" wire:loading.attr="disabled" wire:target="scanSitemap"
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span wire:loading.remove wire:target="scanSitemap">Scanner le site</span>
                        <span wire:loading wire:target="scanSitemap">Scan en cours…</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Ajoute les nouvelles pages, repère celles qui n'existent plus</span>
                    </button>
                    <button type="button" wire:click="openSettings" @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Réglages de distribution
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ \App\Services\PageReleaseService::quota() }} page(s) ouverte(s) au plus par relecteur</span>
                    </button>
                    <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                    <button type="button" wire:click="$set('showResetModal', true)" @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Réinitialiser le suivi du site
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Aucune page n'est retirée ; relancez un scan ensuite</span>
                    </button>
                </div>
            </div>
        </x-slot:actions>
    </x-verif.space-header>

    {{-- Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif
    @if ($scanResult)
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 border border-emerald-200 dark:border-emerald-800 flex items-start gap-3">
            <p class="flex-1 text-sm text-emerald-900 dark:text-emerald-200">
                <strong>Scan terminé :</strong>
                {{ $scanResult['created'] }} page(s) ajoutée(s) · {{ $scanResult['updated'] }} confirmée(s) ·
                {{ $scanResult['marked_obsolete'] }} plus sur le site · {{ $scanResult['total_in_sitemap'] }} pages sur le site
            </p>
            <button type="button" wire:click="dismissScanResult" class="text-emerald-700 dark:text-emerald-300" aria-label="Fermer">×</button>
        </div>
    @endif
    @if ($scanError)
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800 flex items-start gap-3">
            <p class="flex-1 text-sm text-red-900 dark:text-red-200"><strong>Le scan a échoué :</strong> {{ $scanError }}</p>
            <button type="button" wire:click="dismissScanResult" class="text-red-700 dark:text-red-300" aria-label="Fermer">×</button>
        </div>
    @endif

    {{-- Raccourcis et filtres --}}
    @php $quick = $this->activeQuickFilter(); @endphp
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ([
                'all' => ['Toutes', $counts['all']],
                'unassigned' => ['Sans relecteur', $counts['unassigned']],
                'overdue' => ['Échéance dépassée', $counts['overdue']],
                'out_of_site' => ['Plus sur le site', $counts['out_of_site']],
            ] as $key => [$label, $count])
                <button type="button" wire:click="applyQuickFilter('{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs',
                        'border-gray-900 bg-gray-900 text-white dark:border-white dark:bg-white dark:text-gray-900' => $quick === $key,
                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $quick !== $key,
                    ])>
                    {{ $label }} <span class="font-semibold tabular-nums">{{ $count }}</span>
                </button>
            @endforeach
            @if ($filterStage !== '' && $filterStage !== 'unassigned')
                <span class="inline-flex items-center gap-1 rounded-full border border-gray-900 dark:border-white px-3 py-1 text-xs text-gray-900 dark:text-white">
                    Étape : {{ \App\Models\VerificationPage::STAGES[$filterStage]['label'] }}
                    <button type="button" wire:click="$set('filterStage', '')" class="ml-1 text-gray-500 hover:text-gray-900 dark:hover:text-white" aria-label="Retirer le filtre d'étape">×</button>
                </span>
            @endif
        </div>
        <div class="flex flex-col lg:flex-row lg:items-center gap-2">
            <div class="relative flex-1">
                <label for="pages-search" class="sr-only">Rechercher une page</label>
                <input id="pages-search" type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une adresse…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="filterCategory" aria-label="Catégorie"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    <option value="all">Toutes catégories</option>
                    <option value="none">Sans catégorie</option>
                    @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterReviewer" aria-label="Relecteur"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    <option value="">Tous les relecteurs</option>
                    @foreach ($availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterPriority" aria-label="Priorité"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    <option value="all">Toutes priorités</option>
                    <option value="high">Priorité haute</option>
                    <option value="medium">Priorité moyenne</option>
                    <option value="low">Priorité basse</option>
                </select>
                <select wire:model.live="perPage" aria-label="Pages par écran"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    @foreach (\App\Livewire\Verification\Admin\PagesManager::PER_PAGE_OPTIONS as $opt)
                        <option value="{{ $opt }}">{{ $opt }} / page</option>
                    @endforeach
                </select>
                @if ($this->hasActiveFilters())
                    <button type="button" wire:click="resetFilters" class="px-2 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white underline underline-offset-2">
                        Tout afficher
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Pages qui ne sont plus sur le site --}}
    @if ($counts['out_of_site'] > 0)
        <div class="rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                <strong>{{ $counts['out_of_site'] }} page{{ $counts['out_of_site'] > 1 ? 's ne sont' : ' n\'est' }} plus sur le site</strong>
                @if ($lastScan)<span class="text-amber-800 dark:text-amber-200">(dernier scan le {{ $lastScan->format('d/m/Y') }})</span>@endif.
                Leurs relectures sont conservées.
            </p>
            <div class="flex gap-2">
                @unless ($filterOutOfSite)
                    <button type="button" wire:click="applyQuickFilter('out_of_site')" class="px-3 py-1.5 text-xs font-medium text-amber-900 dark:text-amber-100 border border-amber-300 dark:border-amber-700 rounded-md hover:bg-amber-100 dark:hover:bg-amber-900/40">Voir</button>
                @endunless
                <button type="button" wire:click="askRemoveOutOfSite" class="px-3 py-1.5 text-xs font-medium text-amber-900 dark:text-amber-100 border border-amber-300 dark:border-amber-700 rounded-md hover:bg-amber-100 dark:hover:bg-amber-900/40">
                    Retirer du suivi ({{ $counts['out_of_site'] }})
                </button>
            </div>
        </div>
    @endif

    {{-- Barre des actions en masse --}}
    @if (! empty($selectedPageIds))
        <div class="sticky top-0 z-30 rounded-lg border border-gray-900 dark:border-white bg-white dark:bg-gray-800 px-3 py-2 flex flex-col gap-2 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-gray-900 dark:text-white"><strong>{{ count($selectedPageIds) }}</strong> page{{ count($selectedPageIds) > 1 ? 's' : '' }} sélectionnée{{ count($selectedPageIds) > 1 ? 's' : '' }}</p>
                <button type="button" wire:click="clearSelection" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Désélectionner</button>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Livewire\Verification\Admin\PagesManager::BULK_ACTIONS as $action => $label)
                    <button type="button" wire:click="openBulk('{{ $action }}')"
                        @class([
                            'px-2.5 py-1.5 text-xs font-medium rounded-md border',
                            'text-red-700 border-red-200 hover:bg-red-50 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900/20' => $action === 'remove',
                            'text-gray-700 border-gray-300 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' => $action !== 'remove',
                        ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tableau --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $idsOnPage = $pages->pluck('id')->all();
                    $selected = array_map('intval', $selectedPageIds);
                    $allOnPageSelected = ! empty($idsOnPage) && empty(array_diff($idsOnPage, $selected));
                @endphp
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-3 py-2 w-8">
                            <input type="checkbox" @checked($allOnPageSelected)
                                wire:click="toggleSelectAll($event.target.checked, {{ json_encode($idsOnPage) }})"
                                class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white"
                                aria-label="Sélectionner les pages affichées">
                        </th>
                        <x-verif.sortable-th field="title" :active="$this->sortStateFor('title')">Page</x-verif.sortable-th>
                        <x-verif.sortable-th field="stage" :active="$this->sortStateFor('stage')">Étape</x-verif.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Avancement</th>
                        <x-verif.sortable-th field="deadline" :active="$this->sortStateFor('deadline')">Échéance</x-verif.sortable-th>
                        <th class="px-3 py-2 w-10"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($pages as $page)
                        @php
                            $progress = $page->reviewerProgress();
                            $stage = $page->stage();
                            $queued = $progress->where('state', 'wait')->count();
                            $summary = $stage === 'reviewing' ? $page->progressSummary() : null;
                        @endphp
                        <tr wire:key="page-{{ $page->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2 align-top">
                                <input type="checkbox" value="{{ $page->id }}" wire:model.live="selectedPageIds"
                                    class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white"
                                    aria-label="Sélectionner {{ $page->title }}">
                            </td>
                            <td class="px-3 py-2 align-top max-w-md">
                                <div class="flex items-center gap-2">
                                    <x-verif.priority-dot :priority="$page->priority" />
                                    <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate
                                        class="text-sm font-medium text-gray-900 dark:text-white hover:underline truncate">{{ $page->title }}</a>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5 pl-4">
                                    <a href="{{ $page->url }}" target="_blank" rel="noopener"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:underline truncate max-w-[18rem]">{{ \Illuminate\Support\Str::after($page->url, '://') }}</a>
                                    @if ($page->category)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">· {{ $page->categoryLabel() }}</span>
                                    @endif
                                    @if ($page->isOutOfSite())
                                        <x-verif.status-badge size="xs" tone="warn" label="Plus sur le site" title="Absente du dernier scan du site" />
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 align-top whitespace-nowrap">
                                <x-verif.status-badge size="xs" :tone="$page->stageTone()" :label="$page->stageLabel().($summary ? ' · '.$summary : '')" />
                                @if ($stage === 'verified' && $page->nextVerificationAt())
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">jusqu'au {{ $page->nextVerificationAt()->format('d/m/Y') }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top">
                                @if ($progress->isEmpty())
                                    <button type="button" wire:click="openAssignModal({{ $page->id }})"
                                        class="px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                                        + Assigner
                                    </button>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($progress as $row)
                                            <x-verif.reviewer-chip :row="$row" />
                                        @endforeach
                                    </div>
                                    @if ($queued > 0 && $stage !== 'verified')
                                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                            Distribution cette nuit si une place se libère ·
                                            <button type="button" wire:click="releaseNow({{ $page->id }})" class="underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">Distribuer maintenant</button>
                                        </p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top">
                                <x-verif.deadline-badge :page="$page" />
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div x-data="{ open: false }" class="relative inline-block">
                                    <button type="button" @click="open = !open" @click.outside="open = false" title="Actions"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                                        <span class="sr-only">Actions pour {{ $page->title }}</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.opacity
                                        class="absolute right-0 z-40 mt-1 w-56 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1 text-left">
                                        <a href="{{ route('verification.admin.page', $page->id) }}" wire:navigate class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Ouvrir la fiche</a>
                                        <button type="button" wire:click="openAssignModal({{ $page->id }})" @click="open = false" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Relecteurs…</button>
                                        @if ($queued > 0)
                                            <button type="button" wire:click="releaseNow({{ $page->id }})" @click="open = false" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                Distribuer maintenant
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $queued }} relecteur(s) en attente</span>
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openEditModal({{ $page->id }})" @click="open = false" class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Modifier</button>
                                        <a href="{{ $page->url }}" target="_blank" rel="noopener" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Ouvrir sur le site ↗</a>
                                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                                        <button type="button" wire:click="askRemove([{{ $page->id }}])" @click="open = false" class="w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Retirer du suivi</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($this->hasActiveFilters())
                                    Aucune page ne correspond à ces filtres.
                                    <button type="button" wire:click="resetFilters" class="ml-1 underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">Tout afficher</button>
                                @else
                                    Aucune page pour le moment. Ajoutez-en une, ou lancez « Scanner le site » depuis le menu ⋯.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $pages->links() }}</div>
        @endif
    </div>

    {{-- Légende --}}
    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        @foreach (\App\Models\VerificationPage::REVIEWER_STATES as $state => $def)
            <span class="inline-flex items-center gap-1">
                <x-verif.reviewer-chip :row="['user_id' => null, 'name' => '', 'assigned' => true, 'state' => $state, 'label' => $def['label'], 'since' => null, 'review' => null]" class="!border-0 !bg-transparent !px-0" />
                {{ $state === 'done' ? 'a répondu (rien à signaler, ou retour réglé)' : ($state === 'wait' ? 'en attente de distribution ('.\App\Services\PageReleaseService::quota().' page(s) ouverte(s) au plus par relecteur)' : mb_strtolower($def['label'])) }}
            </span>
        @endforeach
    </div>

    {{-- ═══ MODALES ═══ --}}
    @if ($showFormModal)
        @include('livewire.verification.admin.partials.page-form-modal')
    @endif

    @if ($showAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="assign-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeAssignModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Relecteurs de la page</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Un relecteur décoché garde ses relectures ; son assignation est notée au journal de la page.</p>
                    <form wire:submit.prevent="saveAssignment" class="space-y-4">
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-72 overflow-y-auto bg-white dark:bg-gray-700">
                            @forelse ($availableUsers as $user)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" value="{{ $user->id }}" wire:model="quickAssigneeIds" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $user->email }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic">Aucun utilisateur approuvé.</p>
                            @endforelse
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeAssignModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($bulkAction !== '')
        @php $n = count($selectedPageIds); @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="bulk-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeBulk"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                        {{ \App\Livewire\Verification\Admin\PagesManager::BULK_ACTIONS[$bulkAction] }} · {{ $n }} page{{ $n > 1 ? 's' : '' }}
                    </h2>
                    <form wire:submit.prevent="applyBulk" class="space-y-4 mt-3">
                        @if (in_array($bulkAction, ['assign', 'unassign'], true))
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @if ($bulkAction === 'assign')
                                    Les relecteurs cochés sont <strong>ajoutés</strong> à ceux déjà en place, puis distribués au fil des places libres.
                                @else
                                    Les relecteurs cochés sont retirés de ces pages. Leurs relectures déjà rendues sont conservées.
                                @endif
                            </p>
                            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-72 overflow-y-auto bg-white dark:bg-gray-700">
                                @foreach ($availableUsers as $user)
                                    <label class="flex items-center gap-2 py-1 cursor-pointer">
                                        <input type="checkbox" value="{{ $user->id }}" wire:model="bulkUserIds" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('bulkUserIds') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @elseif ($bulkAction === 'priority')
                            <div class="space-y-2">
                                @foreach (['high' => 'Priorité haute', 'medium' => 'Priorité moyenne', 'low' => 'Priorité basse'] as $code => $label)
                                    <label class="flex items-center gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer">
                                        <input type="radio" wire:model="bulkPriority" value="{{ $code }}" class="text-gray-900 focus:ring-gray-900">
                                        <x-verif.priority-dot :priority="$code" />
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif ($bulkAction === 'deadline')
                            <div>
                                <label for="bulk-deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Échéance</label>
                                <input id="bulk-deadline" type="date" wire:model="bulkDeadline" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide pour retirer l'échéance.</p>
                                @error('bulkDeadline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @elseif ($bulkAction === 'category')
                            <div>
                                <label for="bulk-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie</label>
                                <select id="bulk-category" wire:model="bulkCategory" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    <option value="">Aucune</option>
                                    @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($bulkAction === 'release')
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Ces pages apparaissent <strong>tout de suite</strong> sur le tableau de bord de leurs relecteurs en attente,
                                même s'ils ont déjà {{ \App\Services\PageReleaseService::quota() }} page(s) ouverte(s).
                            </p>
                        @elseif ($bulkAction === 'remove')
                            <p class="text-sm text-gray-600 dark:text-gray-400">Vous allez confirmer à l'étape suivante.</p>
                        @endif
                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" wire:click="closeBulk" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">Appliquer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showRemoveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="remove-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="cancelRemove"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6 flex flex-col gap-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        @if ($removing->count() === 1)
                            Retirer « {{ $removing->first()->title }} » du suivi ?
                        @else
                            Retirer {{ $removing->count() }} pages du suivi ?
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if ($removingOutOfSite)
                            Ces pages n'apparaissent plus dans le dernier scan du site.
                        @endif
                        {{ $removing->count() > 1 ? 'Elles disparaissent' : 'Elle disparaît' }} des listes et des tableaux de bord des relecteurs.
                    </p>
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-900 dark:text-green-100">
                        <p class="font-semibold">Rien n'est supprimé</p>
                        <p class="mt-0.5">{{ $removing->sum('all_reviews_count') }} relecture(s), les assignations et le journal sont conservés. Restauration en un clic depuis l'onglet Corbeille.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="cancelRemove" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="button" wire:click="confirmRemove" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Retirer du suivi</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showResetModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="reset-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="$set('showResetModal', false)"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6 flex flex-col gap-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Réinitialiser le suivi du site ?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Toutes les pages sont considérées comme non scannées : « Plus sur le site » disparaît jusqu'au prochain scan,
                        et les pages qui n'existent plus réapparaissent chez les traducteurs. Aucune page, assignation ni relecture n'est retirée.
                    </p>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="$set('showResetModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="button" wire:click="resetSitemapTracking" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg">Réinitialiser</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showSettingsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="settings-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="$set('showSettingsModal', false)"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Réglages de distribution</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Chaque nuit à 3 h 30, chaque relecteur reçoit de nouvelles pages jusqu'à atteindre ce nombre de pages ouvertes en même temps.
                        Une page compte tant qu'il ne l'a pas rendue.
                    </p>
                    <form wire:submit.prevent="saveSettings" class="space-y-4">
                        <div>
                            <label for="settings-quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pages ouvertes au plus par relecteur</label>
                            <input id="settings-quota" type="number" min="{{ \App\Services\PageReleaseService::QUOTA_MIN }}" max="{{ \App\Services\PageReleaseService::QUOTA_MAX }}" wire:model="quota"
                                class="w-28 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Par défaut : {{ \App\Services\PageReleaseService::ACTIVE_PAGES_QUOTA }}.</p>
                            @error('quota') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="$set('showSettingsModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
