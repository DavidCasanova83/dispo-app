{{-- Carte d'un retour de relecteur (onglet À traiter). Requiert le trait ResolvesReviews. --}}
<article wire:key="feedback-{{ $review->id }}"
    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 sm:p-4 flex flex-col gap-2.5">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-flex items-center px-1.5 rounded border border-gray-300 dark:border-gray-600 font-mono text-[11px] text-gray-800 dark:text-gray-200">{{ strtoupper($review->language) }}</span>
        <a href="{{ route('verification.admin.page', $review->page_id) }}" wire:navigate
            class="text-sm font-semibold text-gray-900 dark:text-white hover:underline">{{ $review->page?->title ?? 'Page supprimée' }}</a>
        <span>· {{ $review->user?->name ?? 'Utilisateur supprimé' }}</span>
        <span title="Rendue le {{ $review->submittedAt()->format('d/m/Y à H:i') }}">· {{ $review->submittedAt()->diffForHumans() }}</span>
        @if ($review->language !== 'fr')
            <x-verif.status-badge size="xs" tone="neutral" label="Ancienne relecture de traduction" />
        @endif
        @if ($review->page?->isOutOfSite())
            <x-verif.status-badge size="xs" tone="warn" label="Plus sur le site" />
        @endif
        @if ($review->status === 'in_progress')
            <x-verif.status-badge size="xs" :tone="$review->statusTone()" :label="$review->statusLabel()" />
        @endif
        @if ($claude = ($claudeByPage ?? collect())->get($review->page_id))
            <x-verif.status-badge size="xs" tone="accent" :label="'🤖 Claude : '.$claude->statusLabel()" />
        @endif
    </div>

    <x-verif.review-content :review="$review" />

    @if ($review->admin_response && $replyingTo !== $review->id)
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Votre réponse : <span class="text-gray-700 dark:text-gray-300">« {{ \Illuminate\Support\Str::limit($review->admin_response, 140) }} »</span>
        </p>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <x-verif.review-actions :review="$review" :replying-to="$replyingTo" />
        <a href="{{ route('verification.admin.page', $review->page_id) }}" wire:navigate
            class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">Ouvrir la fiche →</a>
    </div>
</article>
