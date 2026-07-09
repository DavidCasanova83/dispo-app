<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Boîte de retours</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($view === 'list')
                Les pages avec relectures à traiter. Cliquez sur une page pour voir le détail par relecteur.
            @else
                Détail des relectures pour cette page.
            @endif
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Stats (toujours affichées) --}}
    <div class="grid gap-4 grid-cols-2 md:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">À traiter</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_admin'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">En cours</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">À ré-vérifier</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['revision_requested'] }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Validées</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['done'] }}</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- NIVEAU 1 : LISTE DES PAGES                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($view === 'list')

        {{-- Filtres --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4 flex flex-col md:flex-row gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher (titre, URL)…"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <select wire:model.live="filterStatus"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="all">Toutes les pages avec relectures</option>
                <option value="pending_admin">Pages avec relectures à traiter</option>
                <option value="in_progress">Pages avec relectures en cours</option>
                <option value="revision_requested">Pages avec relectures à ré-vérifier</option>
                <option value="done">Pages avec uniquement des relectures validées</option>
            </select>
        </div>

        {{-- Liste des pages --}}
        <div class="space-y-3">
            @forelse ($pages as $page)
                <button type="button" wire:click="openPageDetail({{ $page->id }})"
                    class="w-full text-left rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-base font-semibold text-gray-900 dark:text-white">{{ $page->title }}</span>
                                @php
                                    $pageBadge = match ($page->status) {
                                        'pending' => ['À vérifier', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'],
                                        'in_progress' => ['En cours', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
                                        'needs_fix' => ['À corriger', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
                                        'awaiting_validation' => ['En attente de validation', 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'],
                                        'validated' => ['Validée', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                                        default => [$page->status, 'bg-gray-100 text-gray-800'],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $pageBadge[1] }}">
                                    {{ $pageBadge[0] }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $page->url }}</p>

                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400">
                                <span><strong>{{ $page->total_count }}</strong> review(s)</span>
                                @if ($page->pending_admin_count > 0)
                                    <span class="text-yellow-700 dark:text-yellow-400">⏳ {{ $page->pending_admin_count }} à traiter</span>
                                @endif
                                @if ($page->in_progress_count > 0)
                                    <span class="text-orange-700 dark:text-orange-400">🛠️ {{ $page->in_progress_count }} en cours</span>
                                @endif
                                @if ($page->revision_requested_count > 0)
                                    <span class="text-blue-700 dark:text-blue-400">🔄 {{ $page->revision_requested_count }} à ré-vérifier</span>
                                @endif
                                @if ($page->done_count > 0)
                                    <span class="text-green-700 dark:text-green-400">✅ {{ $page->done_count }} traitée(s)</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-sm text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                            Voir le détail →
                        </div>
                    </div>
                </button>
            @empty
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Aucune page avec relecture pour le moment.
                </div>
            @endforelse
        </div>

        <div>{{ $pages->links() }}</div>

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
            @php
                $pageBadge = match ($page->status) {
                    'pending' => ['À vérifier', 'border-yellow-300 text-yellow-800 dark:border-yellow-700 dark:text-yellow-300'],
                    'in_progress' => ['En cours', 'border-blue-300 text-blue-800 dark:border-blue-700 dark:text-blue-300'],
                    'needs_fix' => ['À corriger', 'border-red-300 text-red-800 dark:border-red-700 dark:text-red-300'],
                    'awaiting_validation' => ['En attente de validation', 'border-purple-300 text-purple-800 dark:border-purple-700 dark:text-purple-300'],
                    'validated' => ['Validée', 'border-green-300 text-green-800 dark:border-green-700 dark:text-green-300'],
                    default => [$page->status, 'border-gray-300 text-gray-800'],
                };
            @endphp
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $pageBadge[1] }}">
                    Statut : {{ $pageBadge[0] }}
                </span>

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
            <div class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Aucune relecture pour cette page.
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

                {{-- Contenu de l'onglet actif --}}
                <div class="p-4 sm:p-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($activeReviews as $review)
                        @php
                            $statusBadge = match ($review->status) {
                                'pending_admin' => ['À traiter', 'border-yellow-300 text-yellow-800 dark:border-yellow-700 dark:text-yellow-300'],
                                'in_progress' => ['En cours', 'border-orange-300 text-orange-800 dark:border-orange-700 dark:text-orange-300'],
                                'revision_requested' => ['À ré-vérifier', 'border-blue-300 text-blue-800 dark:border-blue-700 dark:text-blue-300'],
                                'done' => ['Validée', 'border-green-300 text-green-800 dark:border-green-700 dark:text-green-300'],
                                default => [$review->status, 'border-gray-300 text-gray-800'],
                            };
                            $reviewedUrl = $page->urlForLanguage($review->language);
                        @endphp

                        <div class="py-5 first:pt-0 last:pb-0">
                            {{-- En-tête review --}}
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-sm font-semibold border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
                                        {{ $review->languageLabel() }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusBadge[1] }}">
                                        {{ $statusBadge[0] }}
                                    </span>
                                    @if ($review->rating)
                                        <span class="text-amber-500 text-sm">
                                            @for ($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor
                                            <span class="text-gray-500 ml-1">{{ $review->rating }}/5</span>
                                        </span>
                                    @endif
                                </div>
                                <button wire:click="openResolveModal({{ $review->id }})"
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded whitespace-nowrap">
                                    Traiter
                                </button>
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
                        </div>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400 italic">Aucune relecture de ce relecteur.</p>
                    @endforelse
                </div>
            </div>
        @endif
    @endif

    {{-- Resolve modal (commun aux 2 vues) --}}
    @if ($showResolveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="resolve-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeResolveModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Traiter cette relecture</h2>
                    <form wire:submit.prevent="resolve" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nouveau statut</label>
                            <select wire:model="newStatus"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                <option value="in_progress">En cours de modification</option>
                                <option value="done">Validée (terminé)</option>
                                <option value="revision_requested">Demander une ré-vérification</option>
                            </select>
                            @error('newStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Réponse au relecteur
                                <span class="text-gray-500 font-normal">— visible dans son historique</span>
                            </label>
                            <textarea wire:model="adminResponse" rows="4" maxlength="5000"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                                placeholder="Ex : Merci, j'ai mis à jour la date du marché de Riez."></textarea>
                            @error('adminResponse') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeResolveModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
