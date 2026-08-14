<div class="flex h-full w-full flex-1 flex-col gap-4">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Boîte de retours</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            @if ($view === 'list')
                Les relectures à traiter et les pages déjà vérifiées, avec leur échéance de renouvellement.
            @else
                Détail des relectures pour cette page.
            @endif
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- ═══ TUILES = FILTRES SUR LE STATUT DE LA PAGE ═══ --}}
    {{-- Chaque compteur est le nombre exact de lignes affichées par le filtre. --}}
    <div class="grid gap-2 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
        <x-verif.stat-filter label="Toutes" :count="$stats['all']"
            :active="$filterStatus === 'all'" wire:click="applyStatusFilter('all')" />
        @foreach (\App\Models\VerificationPage::STATUSES as $code => $def)
            <x-verif.stat-filter
                :label="$def['label']"
                :tone="$def['tone']"
                :alert="$code === 'awaiting_validation'"
                :count="$stats[$code]"
                :active="$filterStatus === $code"
                wire:click="applyStatusFilter('{{ $code }}')" />
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- NIVEAU 1 : LISTE DES PAGES                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($view === 'list')

        {{-- Filtres secondaires --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-2">
            <div class="relative flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre, une URL…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-3 py-2 text-sm text-gray-900 dark:text-white">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                </svg>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="filterReviewer"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    title="Filtrer sur un relecteur (ses relectures, ou ses pages assignées si elles sont déjà clôturées)">
                    <option value="">Tous les relecteurs</option>
                    @foreach ($reviewers as $reviewer)
                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
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

        {{-- Tableau --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <x-verif.sortable-th field="title" :active="$this->sortStateFor('title')">Page</x-verif.sortable-th>
                            <x-verif.sortable-th field="pending" :active="$this->sortStateFor('pending')">Relectures</x-verif.sortable-th>
                            <x-verif.sortable-th field="oldest" :active="$this->sortStateFor('oldest')">Plus ancienne</x-verif.sortable-th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relecteurs</th>
                            <x-verif.sortable-th field="next_check" :active="$this->sortStateFor('next_check')">Prochaine vérif.</x-verif.sortable-th>
                            <th class="px-3 py-2 w-10"><span class="sr-only">Détail</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @forelse ($pages as $page)
                            <tr wire:key="inbox-page-{{ $page->id }}"
                                wire:click="openPageDetail({{ $page->id }})"
                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                {{-- Page --}}
                                <td class="px-3 py-2 align-top max-w-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $page->title }}</span>
                                        <x-verif.status-badge size="xs" :tone="$page->statusTone()" :label="$page->statusLabel()" />
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate max-w-[22rem]">
                                        {{ \Illuminate\Support\Str::after($page->url, '://') }}
                                    </p>
                                </td>

                                {{-- Relectures --}}
                                <td class="px-3 py-2 align-top">
                                    @if ($page->total_count > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @if ($page->pending_admin_count > 0)
                                                <x-verif.status-badge size="xs" tone="warn" :label="$page->pending_admin_count . ' à traiter'" />
                                            @endif
                                            @if ($page->in_progress_count > 0)
                                                <x-verif.status-badge size="xs" tone="info" :label="$page->in_progress_count . ' en cours'" />
                                            @endif
                                            @if ($page->revision_requested_count > 0)
                                                <x-verif.status-badge size="xs" tone="accent" :label="$page->revision_requested_count . ' à ré-vérif.'" />
                                            @endif
                                            @if ($page->done_count > 0)
                                                <x-verif.status-badge size="xs" tone="success" :label="$page->done_count . ' traitée(s)'" />
                                            @endif
                                        </div>
                                        @if ($page->avg_rating)
                                            <span class="mt-1 inline-block text-xs text-amber-500" title="Note moyenne des relecteurs">
                                                ★ {{ number_format($page->avg_rating, 1, ',', '') }}/5
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-600" title="Les relectures ont été purgées à la clôture annuelle">
                                            Archivées
                                        </span>
                                    @endif
                                </td>

                                {{-- Ancienneté de la plus vieille relecture --}}
                                <td class="px-3 py-2 align-top">
                                    @if ($page->oldest_review_at)
                                        <span class="text-xs text-gray-600 dark:text-gray-400"
                                            title="{{ \Illuminate\Support\Carbon::parse($page->oldest_review_at)->format('d/m/Y à H:i') }}">
                                            {{ \Illuminate\Support\Carbon::parse($page->oldest_review_at)->diffForHumans(short: true) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
                                    @endif
                                </td>

                                {{-- Relecteurs assignés --}}
                                <td class="px-3 py-2 align-top">
                                    @if ($page->assignees->isEmpty())
                                        <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($page->assignees as $assignee)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300">
                                                    {{ $assignee->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                {{-- Prochaine vérification --}}
                                <td class="px-3 py-2 align-top">
                                    <x-verif.next-check-badge :page="$page" />
                                </td>

                                <td class="px-3 py-2 align-top text-right text-sm text-gray-400">→</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    @if ($this->hasActiveFilters())
                                        Aucune page ne correspond à ces filtres.
                                        <button type="button" wire:click="resetFilters" class="ml-1 underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">
                                            Réinitialiser
                                        </button>
                                    @else
                                        Aucune relecture pour le moment.
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

    @else
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- NIVEAU 2 : DÉTAIL D'UNE PAGE AVEC ONGLETS PAR RELECTEUR        --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}

        {{-- En-tête détail --}}
        <div>
            <button type="button" wire:click="closePageDetail"
                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-3">
                ← Retour à la liste
            </button>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $page->title }}</h2>
            <a href="{{ $page->url }}" target="_blank" rel="noopener"
                class="text-sm text-gray-600 dark:text-gray-400 hover:underline break-all">
                {{ $page->url }} ↗
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-verif.status-badge :tone="$page->statusTone()" :label="$page->statusLabel()" />

                @if ($page->status === 'validated' && $page->validated_at)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Vérifiée le {{ $page->validated_at->translatedFormat('d F Y') }} ·
                        prochaine vérification le {{ $page->nextVerificationAt()->format('d/m/Y') }}
                    </span>
                @endif

                @if ($page->status === 'awaiting_validation')
                    <button type="button" wire:click="openCloseAnnualModal"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 rounded">
                        ✓ Clôturer la vérification annuelle
                    </button>
                @endif
            </div>

            @if ($page->status === 'awaiting_validation')
                <p class="mt-2 text-xs text-purple-700 dark:text-purple-300">
                    Toutes les relectures FR sont traitées. Clôturer la vérification annuelle archivera les relectures et marquera la page comme validée pour 1 an.
                </p>
            @endif
        </div>

        {{-- Onglets relecteurs --}}
        @if ($reviewsByReviewer->isEmpty())
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8 text-center">
                @if ($page->status === 'validated')
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Cette page a été vérifiée et clôturée. Ses relectures ont été purgées lors de la clôture annuelle.
                    </p>
                    @if ($page->nextVerificationAt())
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Elle repassera automatiquement en « à vérifier » le
                            <strong>{{ $page->nextVerificationAt()->translatedFormat('d F Y') }}</strong>
                            @if (($d = $page->daysUntilNextVerification()) > 0)
                                (dans {{ $d }} jour{{ $d > 1 ? 's' : '' }}).
                            @else
                                — le renouvellement est dû, il aura lieu à la prochaine exécution nocturne.
                            @endif
                        </p>
                    @endif
                    @if ($page->assignees->isNotEmpty())
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            Relecteurs conservés pour le prochain cycle :
                            {{ $page->assignees->pluck('name')->join(', ') }}
                        </p>
                    @endif
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune relecture pour cette page.</p>
                @endif
            </div>
        @else
            <div class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <div class="flex">
                        @foreach ($reviewsByReviewer as $userId => $userReviews)
                            @php
                                $firstReview = $userReviews->first();
                                $userName = $firstReview->user?->name ?? 'Utilisateur supprimé';
                                $isActive = $activeReviewerUserId === (int) $userId;
                                $userPendingCount = $userReviews->whereIn('status', ['pending_admin', 'in_progress'])->count();
                                $countLabel = '(' . $userReviews->count();
                                if ($userPendingCount > 0) {
                                    $countLabel .= ', ' . $userPendingCount . ' à traiter';
                                }
                                $countLabel .= ')';
                            @endphp
                            <button type="button" wire:click="selectReviewer({{ $userId }})"
                                class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                                    {{ $isActive
                                        ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                                {{ $userName }}
                                <span class="ml-1.5 text-xs text-gray-500 dark:text-gray-500 font-normal">
                                    {{ $countLabel }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Barre d'action de l'onglet actif --}}
                @php
                    $ouvertes = $activeReviews->whereIn('status', ['pending_admin', 'in_progress'])->count();
                @endphp
                @if ($ouvertes > 0)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $ouvertes }} relecture{{ $ouvertes > 1 ? 's' : '' }} de ce relecteur en attente de traitement.
                        </p>
                        <button type="button" wire:click="validateAllForReviewer({{ $activeReviewerUserId }})"
                            wire:confirm="Marquer comme validées les {{ $ouvertes }} relecture(s) ouverte(s) de ce relecteur ?"
                            class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded whitespace-nowrap">
                            Tout valider ({{ $ouvertes }})
                        </button>
                    </div>
                @endif

                {{-- Contenu de l'onglet actif --}}
                <div class="p-4 sm:p-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($activeReviews as $review)
                        @php $reviewedUrl = $page->urlForLanguage($review->language); @endphp

                        <div class="py-5 first:pt-0 last:pb-0">
                            {{-- En-tête review --}}
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-sm font-semibold border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
                                        {{ $review->languageLabel() }}
                                    </span>
                                    <x-verif.status-badge :tone="$review->statusTone()" :label="$review->statusLabel()" />
                                    @if ($review->rating)
                                        <span class="text-amber-500 text-sm">
                                            @for ($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor
                                            <span class="text-gray-500 ml-1">{{ $review->rating }}/5</span>
                                        </span>
                                    @endif
                                </div>
                                @if ($reviewId === $review->id)
                                    <button wire:click="closeResolvePanel"
                                        class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded whitespace-nowrap">
                                        Fermer
                                    </button>
                                @else
                                    <button wire:click="openResolvePanel({{ $review->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded whitespace-nowrap">
                                        Traiter
                                    </button>
                                @endif
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Soumise le {{ $review->created_at->format('d/m/Y à H:i') }}
                                @if ($review->updated_at && $review->updated_at->ne($review->created_at))
                                    · mise à jour le {{ $review->updated_at->format('d/m/Y à H:i') }}
                                @endif
                            </p>

                            {{-- Vérification factuelle --}}
                            <div class="space-y-3">
                                <h3 class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400">
                                    Vérification factuelle
                                </h3>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 dark:text-gray-400">Contenu :</dt>
                                        <dd class="font-medium {{ $review->content_ok ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                            {{ $review->content_ok ? 'OK' : 'Problème signalé' }}
                                        </dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 dark:text-gray-400">Médias / liens :</dt>
                                        <dd class="font-medium {{ $review->media_ok ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                            {{ $review->media_ok ? 'OK' : 'Problème signalé' }}
                                        </dd>
                                    </div>
                                </dl>

                                @if ($review->content_comment)
                                    <div class="border-l-2 border-red-400 dark:border-red-600 pl-3 py-1">
                                        <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Problème de contenu</p>
                                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->content_comment }}</p>
                                    </div>
                                @endif
                                @if ($review->media_comment)
                                    <div class="border-l-2 border-red-400 dark:border-red-600 pl-3 py-1">
                                        <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Problème de médias / liens</p>
                                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->media_comment }}</p>
                                    </div>
                                @endif
                                @if ($review->remarks)
                                    <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 py-1">
                                        <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Remarques</p>
                                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->remarks }}</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Évaluation éditoriale --}}
                            @if ($review->hasEditorialAssessment())
                                <div class="mt-5 space-y-3">
                                    <h3 class="text-xs uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400">
                                        Évaluation éditoriale
                                    </h3>

                                    <dl class="text-sm space-y-1.5">
                                        @if ($review->relevance)
                                            <div class="flex gap-2">
                                                <dt class="text-gray-500 dark:text-gray-400 min-w-[110px]">Pertinence :</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">{{ $review->relevanceLabel() }}</dd>
                                            </div>
                                        @endif
                                        @if ($review->content_to_remove)
                                            <div class="flex gap-2">
                                                <dt class="text-gray-500 dark:text-gray-400 min-w-[110px]">À supprimer :</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">{{ $review->contentToRemoveLabel() }}</dd>
                                            </div>
                                        @endif
                                        @if ($review->visitor_perspective)
                                            <div class="flex gap-2">
                                                <dt class="text-gray-500 dark:text-gray-400 min-w-[110px]">Vue visiteur :</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">{{ $review->visitorPerspectiveLabel() }}</dd>
                                            </div>
                                        @endif
                                    </dl>

                                    @if (! empty($review->content_to_add))
                                        <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 py-1">
                                            <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">À ajouter</p>
                                            <ul class="text-sm text-gray-800 dark:text-gray-200 list-disc list-inside space-y-0.5">
                                                @foreach ($review->contentToAddLabels() as $label)
                                                    <li>{{ $label }}</li>
                                                @endforeach
                                            </ul>
                                            @if ($review->content_to_add_details)
                                                <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300 italic">{{ $review->content_to_add_details }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($review->content_to_remove_details)
                                        <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 py-1">
                                            <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Détail (à supprimer)</p>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->content_to_remove_details }}</p>
                                        </div>
                                    @endif

                                    @if ($review->visitor_unanswered)
                                        <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 py-1">
                                            <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Question sans réponse</p>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->visitor_unanswered }}</p>
                                        </div>
                                    @endif

                                    @if ($review->suggestions)
                                        <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 py-1">
                                            <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Suggestions</p>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->suggestions }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Liens & réponse admin --}}
                            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2">
                                @if ($reviewedUrl)
                                    <a href="{{ $reviewedUrl }}" target="_blank" rel="noopener"
                                        class="text-sm text-gray-700 dark:text-gray-300 hover:underline">
                                        Voir la page {{ $review->languageLabel() }} ↗
                                    </a>
                                @endif
                            </div>

                            @if ($review->admin_response)
                                <div class="mt-4 border-l-2 border-gray-900 dark:border-white pl-3 py-1">
                                    <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Votre réponse</p>
                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->admin_response }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        {{ $review->admin_response_at?->format('d/m/Y à H:i') }}
                                    </p>
                                </div>
                            @endif

                            {{-- ═══ PANNEAU DE TRAITEMENT INLINE ═══ --}}
                            @if ($reviewId === $review->id)
                                <div class="mt-4 rounded-lg border border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900/40 p-4"
                                    wire:key="resolve-panel-{{ $review->id }}">
                                    <form wire:submit.prevent="resolve" class="space-y-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Que faites-vous de cette relecture ?</p>
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                @foreach ([
                                                    'in_progress' => ['En cours de modification', 'Vous prenez le sujet en main'],
                                                    'done' => ['Validée', 'Le point est réglé'],
                                                    'revision_requested' => ['Ré-vérification', 'La page repart chez le relecteur'],
                                                ] as $code => [$label, $hint])
                                                    <label class="flex items-start gap-2 p-2.5 rounded border cursor-pointer transition-colors
                                                        {{ $newStatus === $code
                                                            ? 'border-gray-900 bg-white dark:border-white dark:bg-gray-800'
                                                            : 'border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800' }}">
                                                        <input type="radio" wire:model.live="newStatus" value="{{ $code }}"
                                                            class="mt-0.5 text-gray-900 focus:ring-gray-900 dark:text-white dark:focus:ring-white">
                                                        <span>
                                                            <span class="block text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('newStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                                Réponse au relecteur
                                                <span class="text-gray-500 font-normal">— optionnel, visible dans son historique</span>
                                            </label>
                                            <textarea wire:model="adminResponse" rows="3" maxlength="5000"
                                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                                                placeholder="Ex : Merci, j'ai mis à jour la date du marché de Riez."></textarea>
                                            @error('adminResponse') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                            @if ($review->admin_response)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    Videz ce champ pour supprimer le message existant.
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="closeResolvePanel"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-white dark:hover:bg-gray-700 rounded">
                                                Annuler
                                            </button>
                                            <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded">
                                                Enregistrer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400 italic">Aucune relecture de ce relecteur.</p>
                    @endforelse
                </div>
            </div>
        @endif
    @endif

    {{-- Close annual modal --}}
    @if ($showCloseAnnualModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="close-annual-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeCloseAnnualModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Clôturer la vérification annuelle</h2>
                    <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                        <p>
                            Vous êtes sur le point de <strong>clôturer la vérification annuelle</strong> de cette page.
                        </p>
                        <div class="rounded border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-3 text-red-800 dark:text-red-200">
                            <p class="font-medium mb-1">⚠️ Action irréversible :</p>
                            <ul class="list-disc list-inside space-y-0.5 text-xs">
                                <li>Toutes les relectures (FR, EN, IT) de cette page seront <strong>supprimées</strong>.</li>
                                <li>L'historique des relecteurs pour cette page sera vidé.</li>
                                <li>La page passera en statut <strong>« Validée »</strong> pour 1 an.</li>
                                <li>Les assignations sont conservées : les mêmes relecteurs reverront la page lors du prochain cycle.</li>
                            </ul>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Dans ~365 jours, la page repassera automatiquement en « À vérifier » et sera redistribuée.
                        </p>
                    </div>
                    <div class="flex justify-end gap-2 pt-5">
                        <button type="button" wire:click="closeCloseAnnualModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            Annuler
                        </button>
                        <button type="button" wire:click="confirmCloseAnnual"
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 rounded">
                            Confirmer la clôture
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
