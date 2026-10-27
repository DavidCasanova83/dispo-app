@props([
    'review',
    // false : seulement les problèmes signalés ; true : toutes les réponses
    'full' => false,
])

{{-- Contenu d'une relecture : problèmes signalés, puis (full) évaluation éditoriale et réponse de l'admin. --}}
<div class="flex flex-col gap-2 text-sm">
    @if (! $review->content_ok)
        <div class="border-l-2 border-red-400 dark:border-red-600 pl-3">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Problème de contenu</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $review->content_comment ?: 'Non précisé' }}</p>
        </div>
    @endif
    @if (! $review->media_ok)
        <div class="border-l-2 border-red-400 dark:border-red-600 pl-3">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Médias / liens</p>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $review->media_comment ?: 'Non précisé' }}</p>
        </div>
    @endif
    @if ($review->remarks)
        <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Remarques</p>
            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->remarks }}</p>
        </div>
    @endif

    @if ($full && $review->hasEditorialAssessment())
        <details class="group">
            <summary class="cursor-pointer text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Évaluation éditoriale
                @if ($review->rating)
                    <span class="text-amber-500">· {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                @endif
            </summary>
            <dl class="mt-2 grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                @if ($review->relevance)
                    <dt class="text-gray-500 dark:text-gray-400">Pertinence</dt><dd class="text-gray-900 dark:text-white">{{ $review->relevanceLabel() }}</dd>
                @endif
                @if (! empty($review->content_to_add))
                    <dt class="text-gray-500 dark:text-gray-400">À ajouter</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ implode(' · ', $review->contentToAddLabels()) }}
                        @if ($review->content_to_add_details)<span class="block italic text-gray-600 dark:text-gray-300">{{ $review->content_to_add_details }}</span>@endif
                    </dd>
                @endif
                @if ($review->content_to_remove)
                    <dt class="text-gray-500 dark:text-gray-400">À supprimer</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ $review->contentToRemoveLabel() }}
                        @if ($review->content_to_remove_details)<span class="block italic text-gray-600 dark:text-gray-300">{{ $review->content_to_remove_details }}</span>@endif
                    </dd>
                @endif
                @if ($review->visitor_perspective)
                    <dt class="text-gray-500 dark:text-gray-400">Vue visiteur</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ $review->visitorPerspectiveLabel() }}
                        @if ($review->visitor_unanswered)<span class="block italic text-gray-600 dark:text-gray-300">{{ $review->visitor_unanswered }}</span>@endif
                    </dd>
                @endif
                @if ($review->suggestions)
                    <dt class="text-gray-500 dark:text-gray-400">Suggestions</dt><dd class="text-gray-900 dark:text-white whitespace-pre-line">{{ $review->suggestions }}</dd>
                @endif
            </dl>
        </details>
    @endif

    @if ($full && $review->admin_response)
        <div class="border-l-2 border-gray-900 dark:border-white pl-3">
            <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Réponse au relecteur · {{ $review->admin_response_at?->format('d/m/Y à H:i') }}</p>
            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $review->admin_response }}</p>
        </div>
    @endif
</div>
