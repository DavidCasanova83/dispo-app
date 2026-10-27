<div class="flex h-full w-full flex-1 flex-col gap-4">
    <x-verif.space-header active="page" :stage="$page->trashed() ? null : $page->stage()" />

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if ($page->trashed())
        <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-sm text-red-900 dark:text-red-100">
                Cette page est dans la corbeille depuis le {{ $page->deleted_at->format('d/m/Y') }}. Rien n'a été supprimé : relectures et journal sont intacts.
            </p>
            <button type="button" wire:click="restorePage" class="px-3 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 rounded-md">Restaurer</button>
        </div>
    @endif

    {{-- En-tête de la fiche --}}
    <div class="flex flex-col gap-3">
        <nav class="text-xs text-gray-500 dark:text-gray-400" aria-label="Fil d'Ariane">
            <a href="{{ route('verification.admin.pages') }}" wire:navigate class="hover:underline">Pages</a> › {{ $page->title }}
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $page->title }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ $page->url }}" target="_blank" rel="noopener" class="hover:underline break-all">{{ \Illuminate\Support\Str::after($page->url, '://') }} ↗</a>
                    @if ($page->categoryLabel())<span>{{ $page->categoryLabel() }}</span>@endif
                    <span class="inline-flex items-center gap-1"><x-verif.priority-dot :priority="$page->priority" /> {{ $page->priorityLabel() }}</span>
                    @if ($page->deadline)<span>Échéance : {{ $page->deadline->format('d/m/Y') }}</span>@endif
                    @if ($page->theme)<span title="Ancien champ « Thème », conservé">Thème : {{ $page->theme }}</span>@endif
                    @if ($page->isOutOfSite())<x-verif.status-badge size="xs" tone="warn" label="Plus sur le site" />@endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-verif.status-badge :tone="$page->stageTone()" :label="$page->stageLabel()" />
                @if ($page->status === 'awaiting_validation' && ! $page->trashed())
                    <button type="button" wire:click="askClose"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                        Clôturer
                    </button>
                @endif
                <button type="button" wire:click="openEditModal({{ $page->id }})"
                    class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Modifier
                </button>
                @unless ($page->trashed())
                    <button type="button" wire:click="$set('showRemoveModal', true)"
                        class="px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                        Retirer du suivi
                    </button>
                @endunless
            </div>
        </div>

        {{-- Étapes du cycle en cours --}}
        @php
            $steps = ['Relecteurs assignés', 'En relecture', 'Retours traités', 'Prête à clôturer', 'Vérifiée pour 1 an'];
            $reached = match ($page->stage()) {
                'unassigned' => 0,
                'queued' => 1,
                'reviewing' => 1,
                'to_handle', 'correcting' => 2,
                'ready' => 3,
                'verified' => 5,
            };
        @endphp
        <ol class="grid grid-cols-5 gap-1" aria-label="Avancement du cycle {{ $page->cycle }}">
            @foreach ($steps as $i => $label)
                <li class="flex flex-col gap-1 text-[11px] {{ $i < $reached ? 'text-gray-600 dark:text-gray-300' : ($i === $reached ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-400 dark:text-gray-500') }}">
                    <span class="h-1 rounded-full {{ $i < $reached ? 'bg-green-600' : ($i === $reached ? 'bg-amber-500' : 'bg-gray-200 dark:bg-gray-700') }}"></span>
                    {{ $label }}
                </li>
            @endforeach
        </ol>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_18rem] gap-4 items-start">
        <div class="flex flex-col gap-4 min-w-0">
        {{-- ═══ RETOURS DU CYCLE EN COURS ═══ --}}
        <section class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex flex-col gap-4 min-w-0">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Retours du cycle en cours (cycle {{ $page->cycle }})
            </h3>

            @forelse ($progress as $row)
                <div class="flex flex-col gap-2 {{ ! $loop->first ? 'pt-4 border-t border-gray-100 dark:border-gray-700' : '' }}" wire:key="progress-{{ $row['user_id'] ?? 'x'.$loop->index }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <strong>{{ $row['name'] }}</strong>
                            @unless ($row['assigned'])<span class="text-xs text-gray-500 dark:text-gray-400">(plus assigné)</span>@endunless
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                · {{ match ($row['state']) {
                                    'wait' => 'en attente de distribution',
                                    'open' => 'page reçue le '.$row['since']->format('d/m/Y').', pas encore rendue',
                                    default => 'rendue le '.$row['review']->submittedAt()->format('d/m/Y'),
                                } }}
                            </span>
                        </p>
                        @if ($row['review'])
                            <x-verif.status-badge size="xs" :tone="$row['review']->statusTone()" :label="$row['review']->statusLabel()" />
                        @else
                            <x-verif.status-badge size="xs" :tone="\App\Models\VerificationPage::REVIEWER_STATES[$row['state']]['tone']" :label="$row['label']" />
                        @endif
                    </div>
                    @if ($row['review'])
                        <x-verif.review-content :review="$row['review']" full />
                        <x-verif.review-actions :review="$row['review']" :replying-to="$replyingTo" />
                    @elseif ($row['state'] === 'wait' && ! $page->trashed())
                        <div>
                            <button type="button" wire:click="releaseNow({{ $row['user_id'] }})"
                                class="px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                                Distribuer maintenant
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($page->status === 'validated')
                        Page vérifiée le {{ $page->validated_at?->format('d/m/Y') }} : le prochain cycle commence le {{ $page->nextVerificationAt()?->format('d/m/Y') }}.
                        Les relectures du cycle clôturé sont dans l'historique.
                    @else
                        Aucun relecteur pour l'instant. Ajoutez-en un dans le panneau « Relecteurs ».
                    @endif
                </p>
            @endforelse

            @if ($translationReviews->isNotEmpty())
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-col gap-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Anciennes relectures EN / IT</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rendues avant que les traductions passent aux traducteurs. Conservées ; elles ne comptent pas dans l'étape de la page.</p>
                    @foreach ($translationReviews as $review)
                        <div class="flex flex-col gap-2" wire:key="tr-review-{{ $review->id }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-1.5 rounded border border-gray-300 dark:border-gray-600 font-mono text-[11px]">{{ strtoupper($review->language) }}</span>
                                    <strong>{{ $review->user?->name ?? 'Utilisateur supprimé' }}</strong>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">· rendue le {{ $review->submittedAt()->format('d/m/Y') }}</span>
                                </p>
                                <x-verif.status-badge size="xs" :tone="$review->statusTone()" :label="$review->statusLabel()" />
                            </div>
                            <x-verif.review-content :review="$review" full />
                            <x-verif.review-actions :review="$review" :replying-to="$replyingTo" />
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ═══ CORRECTION PAR CLAUDE (super-admin) ═══ --}}
        @if ($canAskClaude && ($claudeOpenReviews->isNotEmpty() || $claudeRequests->isNotEmpty()))
            @php $claudeRequest = $claudeRequests->first(); @endphp
            <section class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50/60 dark:bg-purple-900/10 p-4 flex flex-col gap-3" aria-labelledby="claude-title">
                <div class="flex items-center justify-between gap-2">
                    <h3 id="claude-title" class="text-xs font-semibold uppercase tracking-wide text-purple-800 dark:text-purple-300">🤖 Correction par Claude</h3>
                    @if ($claudeRequest)
                        <x-verif.status-badge size="xs" :tone="$claudeRequest->statusTone()" :label="$claudeRequest->statusLabel()" />
                    @endif
                </div>

                @if ($claudeRequest)
                    <div class="flex flex-col gap-1">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Demandée le {{ $claudeRequest->sent_at?->translatedFormat('d F Y à H:i') }}
                            par {{ $claudeRequest->requester?->name ?? 'un utilisateur supprimé' }}
                            · {{ $claudeRequest->reviewCount() }} retour(s)
                            @if ($claudeRequest->issue_url)
                                · <a href="{{ $claudeRequest->issue_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">issue #{{ $claudeRequest->issue_number }} ↗</a>
                            @endif
                            @if ($claudeRequest->pr_url)
                                · <a href="{{ $claudeRequest->pr_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">PR #{{ $claudeRequest->pr_number }} ↗</a>
                            @endif
                        </p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ match ($claudeRequest->status) {
                                'sent' => 'Claude corrige la page. La PR sera créée automatiquement dès qu\'il aura fini (vérification toutes les 5 minutes).',
                                'pr_open' => 'PR à relire et fusionner sur GitHub.',
                                'merged' => 'PR fusionnée : après déploiement, vérifiez la page en ligne puis marquez les retours « Réglé ».',
                                'closed' => 'PR fermée sans fusion : rien n\'a été modifié sur le site.',
                                'no_change' => 'Claude n\'a rien modifié dans le dépôt : sa réponse est dans l\'issue (page de l\'admin WordPress à corriger à la main, ou retours non applicables).',
                                default => '',
                            } }}
                        </p>
                        @if ($claudeRequest->error)
                            <p class="text-sm {{ $claudeRequest->status === 'failed' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $claudeRequest->error }}</p>
                        @endif
                        @if ($claudeRequest->isActive())
                            <button type="button" wire:click="refreshClaude" wire:loading.attr="disabled"
                                class="self-start text-sm text-purple-800 dark:text-purple-300 hover:underline underline-offset-2">
                                🔄 Vérifier maintenant
                            </button>
                        @endif
                        @if ($claudeRequests->count() > 1)
                            <details class="text-xs text-gray-600 dark:text-gray-400">
                                <summary class="cursor-pointer">Demandes précédentes</summary>
                                <ul class="mt-1 flex flex-col gap-0.5">
                                    @foreach ($claudeRequests->skip(1) as $previous)
                                        <li>
                                            {{ $previous->sent_at?->format('d/m/Y') }} · {{ $previous->statusLabel() }}
                                            @if ($previous->issue_url) · <a href="{{ $previous->issue_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline">issue #{{ $previous->issue_number }}</a>@endif
                                            @if ($previous->pr_url) · <a href="{{ $previous->pr_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline">PR #{{ $previous->pr_number }}</a>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endif

                @if ($claudeOpenReviews->isNotEmpty() && ! $claudeRequest?->isActive())
                    <div class="flex flex-col gap-2 {{ $claudeRequest ? 'pt-3 border-t border-purple-200 dark:border-purple-800' : '' }}">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Claude corrige la page à partir des retours cochés, puis le panel crée une PR à relire avant de fusionner.
                            Les noms des relecteurs ne sont pas transmis ; les retours « Nouveau » passent « En correction ».
                        </p>
                        <fieldset class="flex flex-col gap-1">
                            <legend class="sr-only">Retours à transmettre</legend>
                            @foreach ($claudeOpenReviews as $review)
                                <label class="flex items-start gap-2 text-sm text-gray-800 dark:text-gray-200 cursor-pointer" wire:key="claude-review-{{ $review->id }}">
                                    <input type="checkbox" value="{{ $review->id }}" wire:model="claudeReviewIds"
                                        class="mt-0.5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                    <span class="min-w-0">
                                        <span class="font-mono text-[11px]">{{ strtoupper($review->language) }}</span>
                                        {{ $review->user?->name ?? 'Utilisateur supprimé' }}
                                        <span class="text-gray-500 dark:text-gray-400">· {{ $review->statusLabel() }} ·</span>
                                        <span class="text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($review->content_comment ?: ($review->media_comment ?: ($review->remarks ?: 'Voir le retour')), 90) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>
                        <div>
                            <label for="claude-message" class="text-sm font-medium text-gray-900 dark:text-white">Précisions pour Claude (facultatif)</label>
                            <textarea id="claude-message" wire:model="claudeMessage" rows="2" maxlength="3000"
                                placeholder="Les retours cochés sont transmis tels quels. Ex : ne pas toucher au bloc des horaires."
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white"></textarea>
                            @error('claudeMessage') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        @if ($claudeConfigured)
                            <button type="button" wire:click="askClaude" wire:loading.attr="disabled"
                                wire:confirm="Envoyer les retours cochés à Claude ? Une issue GitHub est créée dans le dépôt du site ; Claude corrige la page et une PR est créée automatiquement, à relire avant de fusionner."
                                class="self-start px-3 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 rounded-lg">
                                🤖 {{ $claudeRequest ? 'Renvoyer à Claude' : 'Demander la correction à Claude' }}
                            </button>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Connexion à GitHub non configurée (<code>CLAUDE_GITHUB_TOKEN</code>, voir DEMANDES_CLAUDE.md).
                            </p>
                        @endif
                    </div>
                @endif
                @error('claude') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </section>
        @endif
        </div>

        {{-- ═══ COLONNE LATÉRALE ═══ --}}
        <aside class="flex flex-col gap-4">
            <section class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex flex-col gap-2">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Relecteurs</h3>
                @forelse ($progress->where('assigned', true) as $row)
                    <div class="flex items-center justify-between gap-2 text-sm" wire:key="assignee-{{ $row['user_id'] }}">
                        <x-verif.reviewer-chip :row="$row" />
                        @if ($confirmRemoveReviewer === $row['user_id'])
                            <span class="flex items-center gap-1">
                                <button type="button" wire:click="removeReviewer({{ $row['user_id'] }})" class="text-xs text-red-600 dark:text-red-400 underline">Retirer</button>
                                <button type="button" wire:click="$set('confirmRemoveReviewer', null)" class="text-xs text-gray-500">Annuler</button>
                            </span>
                        @elseif (! $page->trashed())
                            <button type="button" wire:click="$set('confirmRemoveReviewer', {{ $row['user_id'] }})"
                                class="text-xs text-gray-400 hover:text-red-600 dark:hover:text-red-400" title="Retirer {{ $row['name'] }} (ses relectures sont conservées)">Retirer</button>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucun relecteur.</p>
                @endforelse
                @unless ($page->trashed())
                    <form wire:submit.prevent="addReviewer" class="flex gap-2 pt-1">
                        <label for="add-reviewer" class="sr-only">Ajouter un relecteur</label>
                        <select id="add-reviewer" wire:model="addReviewerId" class="min-w-0 flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-sm text-gray-900 dark:text-white">
                            <option value="">Ajouter un relecteur…</option>
                            @foreach ($addableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">Ajouter</button>
                    </form>
                    @if ($progress->where('state', 'wait')->isNotEmpty())
                        <button type="button" wire:click="releaseNow" class="self-start text-xs text-gray-600 dark:text-gray-400 underline underline-offset-2 hover:text-gray-900 dark:hover:text-white">
                            Distribuer maintenant à tous ceux en attente
                        </button>
                    @endif
                @endunless
            </section>

            <section class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex flex-col gap-2">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Traductions</h3>
                @foreach (\App\Models\TranslationCheck::LANGUAGES as $code => $lang)
                    @php $check = $page->translationCheckFor($code); @endphp
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="text-gray-900 dark:text-white">{{ $lang['flag'] }} {{ $lang['short'] }}</span>
                        @if ($page->urlForLanguage($code))
                            <x-verif.status-badge size="xs" :tone="\App\Models\TranslationCheck::statusToneFor($check)" :label="\App\Models\TranslationCheck::statusLabelFor($check)" />
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">adresse à renseigner</span>
                        @endif
                    </div>
                @endforeach
                @can('manage-translations')
                    <a href="{{ route('verification.admin.translations', ['fiche' => $page->id]) }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Ouvrir le suivi des traductions →</a>
                @endcan
            </section>

            <section class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex flex-col gap-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Historique</h3>

                @forelse ($archived as $cycle => $reviews)
                    @php $closure = $closures->get($cycle); @endphp
                    <div class="rounded-md border border-gray-200 dark:border-gray-700" wire:key="cycle-{{ $cycle }}">
                        <button type="button" wire:click="$set('openCycle', {{ $openCycle === $cycle ? 'null' : $cycle }})"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left text-sm">
                            <span>
                                <span class="font-medium text-gray-900 dark:text-white">Cycle {{ $cycle }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    @if ($closure) clôturé le {{ $closure->created_at->format('d/m/Y') }} @else archivé le {{ $reviews->first()->archived_at->format('d/m/Y') }} @endif
                                    · {{ $reviews->count() }} relecture(s)
                                </span>
                            </span>
                            <span class="text-xs text-indigo-600 dark:text-indigo-400">{{ $openCycle === $cycle ? 'Masquer' : 'Consulter' }}</span>
                        </button>
                        @if ($openCycle === $cycle)
                            <div class="px-3 pb-3 flex flex-col gap-3 border-t border-gray-100 dark:border-gray-700 pt-2">
                                @foreach ($reviews as $review)
                                    <div class="flex flex-col gap-1">
                                        <p class="text-xs text-gray-700 dark:text-gray-300">
                                            <span class="font-mono">{{ strtoupper($review->language) }}</span> ·
                                            <strong>{{ $review->user?->name ?? 'Utilisateur supprimé' }}</strong> ·
                                            {{ $review->statusLabel() }} · rendue le {{ $review->submittedAt()->format('d/m/Y') }}
                                            @if ($review->rating) · {{ $review->rating }}/5 @endif
                                        </p>
                                        <x-verif.review-content :review="$review" full />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400">Aucun cycle clôturé conservé pour cette page.</p>
                @endforelse

                @if ($events->isNotEmpty())
                    <div class="flex flex-col gap-1.5 pt-1">
                        <h4 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Journal</h4>
                        <ol class="flex flex-col gap-1.5 text-xs">
                            @foreach ($events as $event)
                                <li class="grid grid-cols-[4.5rem_1fr] gap-2" wire:key="event-{{ $event->id }}">
                                    <time class="text-gray-400 dark:text-gray-500 tabular-nums" datetime="{{ $event->created_at->toIso8601String() }}" title="{{ $event->created_at->format('d/m/Y à H:i') }}">{{ $event->created_at->format('d/m/y') }}</time>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $event->describe() }}
                                        @if ($event->type === 'review_resolved' && ! empty($event->data['message']))
                                            <span class="block italic text-gray-500 dark:text-gray-400">« {{ \Illuminate\Support\Str::limit($event->data['message'], 160) }} »</span>
                                        @endif
                                        @if ($event->type === 'review_submitted' && ! empty($event->data['previous']))
                                            <details class="mt-0.5">
                                                <summary class="cursor-pointer text-gray-500 dark:text-gray-400">Version précédente</summary>
                                                <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 text-gray-600 dark:text-gray-400">
                                                    @foreach (['content_comment' => 'Contenu', 'media_comment' => 'Médias / liens', 'remarks' => 'Remarques', 'admin_response' => 'Réponse de l\'admin'] as $field => $label)
                                                        @if (! empty($event->data['previous'][$field]))
                                                            <dt>{{ $label }}</dt><dd class="whitespace-pre-line">{{ $event->data['previous'][$field] }}</dd>
                                                        @endif
                                                    @endforeach
                                                </dl>
                                            </details>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                        @if ($eventsTotal > $events->count())
                            <button type="button" wire:click="$set('showFullJournal', true)" class="self-start text-xs text-gray-600 dark:text-gray-400 underline underline-offset-2">Tout le journal ({{ $eventsTotal }})</button>
                        @endif
                    </div>
                @endif
            </section>
        </aside>
    </div>

    {{-- ═══ MODALES ═══ --}}
    @if ($showFormModal)
        @include('livewire.verification.admin.partials.page-form-modal')
    @endif

    @if ($showCloseModal)
        @include('livewire.verification.admin.partials.close-modal', ['closing' => collect([tap(clone $page, fn ($p) => $p->reviews_count = $closingCount)])])
    @endif

    @if ($showRemoveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="remove-page-modal">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="$set('showRemoveModal', false)"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6 flex flex-col gap-3">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Retirer « {{ $page->title }} » du suivi ?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Elle disparaît des listes et des tableaux de bord des relecteurs.</p>
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-900 dark:text-green-100">
                        <p class="font-semibold">Rien n'est supprimé</p>
                        <p class="mt-0.5">Relectures, assignations et journal sont conservés. Restauration en un clic depuis l'onglet Corbeille.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="$set('showRemoveModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Annuler</button>
                        <button type="button" wire:click="confirmRemovePage" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Retirer du suivi</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
