<div class="flex h-full w-full flex-1 flex-col gap-6 max-w-3xl mx-auto">
    {{-- Back link --}}
    <div>
        <a href="{{ route('verification.index') }}" wire:navigate
            class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline">
            ← Retour au dashboard
        </a>
    </div>

    {{-- Header --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
            📜 Mon historique de vérifications
        </h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Vous avez contribué à {{ $totalDone }} vérification{{ $totalDone > 1 ? 's' : '' }}. Merci !
        </p>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4 flex flex-col sm:flex-row gap-3">
        <select wire:model.live="filterStatus"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toutes</option>
            <option value="done">✅ Validées</option>
            <option value="in_progress">🛠️ En cours de modification</option>
            <option value="revision_requested">🔄 À ré-vérifier</option>
            <option value="pending_admin">⏳ En attente</option>
        </select>
        <select wire:model.live="filterPeriod"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
            <option value="all">Toute période</option>
            <option value="this_year">Cette année</option>
            <option value="this_month">Ce mois</option>
        </select>
    </div>

    {{-- List --}}
    <div class="space-y-3">
        @forelse ($reviews as $review)
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $review->page?->title ?? '—' }}
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $review->languageFlag() }} {{ $review->languageLabel() }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    Vérifiée le {{ $review->created_at->translatedFormat('d F Y') }}
                    @if ($review->rating)
                        <span class="ml-2 text-yellow-500">
                            @for ($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor
                        </span>
                    @endif
                </p>
                <p class="text-sm mb-2">
                    Statut : <strong>{{ $review->userFacingStatus() }}</strong>
                </p>
                @php
                    $hasAnyComment = $review->content_comment || $review->media_comment || $review->remarks
                        || $review->hasEditorialAssessment();
                @endphp
                @if ($hasAnyComment)
                    <details class="text-sm text-gray-700 dark:text-gray-300 mt-2">
                        <summary class="cursor-pointer hover:underline">Voir mes commentaires</summary>
                        <div class="mt-2 space-y-2">
                            @if ($review->content_comment)
                                <div class="rounded bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-3">
                                    <strong class="block mb-1">⚠️ Problème de contenu :</strong>
                                    {{ $review->content_comment }}
                                </div>
                            @endif
                            @if ($review->media_comment)
                                <div class="rounded bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-3">
                                    <strong class="block mb-1">⚠️ Problème de médias/liens :</strong>
                                    {{ $review->media_comment }}
                                </div>
                            @endif
                            @if ($review->remarks)
                                <div class="rounded bg-gray-50 dark:bg-gray-900/40 p-3">
                                    <strong class="block mb-1">Remarques :</strong>
                                    {{ $review->remarks }}
                                </div>
                            @endif

                            @if ($review->hasEditorialAssessment())
                                <div class="rounded bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 space-y-1">
                                    <strong class="block mb-1">💡 Évaluation éditoriale :</strong>
                                    @if ($review->relevance)
                                        <div>📊 Pertinence : {{ $review->relevanceLabel() }}</div>
                                    @endif
                                    @if (! empty($review->content_to_add))
                                        <div>
                                            ➕ À ajouter :
                                            <ul class="list-disc list-inside ml-4">
                                                @foreach ($review->contentToAddLabels() as $label)
                                                    <li>{{ $label }}</li>
                                                @endforeach
                                            </ul>
                                            @if ($review->content_to_add_details)
                                                <div class="italic ml-1">« {{ $review->content_to_add_details }} »</div>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($review->content_to_remove)
                                        <div>
                                            ➖ À supprimer : {{ $review->contentToRemoveLabel() }}
                                            @if ($review->content_to_remove_details)
                                                <div class="italic ml-1">« {{ $review->content_to_remove_details }} »</div>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($review->visitor_perspective)
                                        <div>
                                            👁️ Vue visiteur : {{ $review->visitorPerspectiveLabel() }}
                                            @if ($review->visitor_unanswered)
                                                <div class="italic ml-1">« {{ $review->visitor_unanswered }} »</div>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($review->suggestions)
                                        <div class="mt-1">
                                            <strong>Suggestions :</strong> {{ $review->suggestions }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
                @if ($review->admin_response)
                    <div class="rounded bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-3 text-sm text-indigo-900 dark:text-indigo-200 mt-2">
                        <strong class="block mb-1">Réponse de David :</strong>
                        {{ $review->admin_response }}
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Aucune vérification à afficher.
            </div>
        @endforelse
    </div>

    <div>{{ $reviews->links() }}</div>
</div>
