@props([
    'review',
    // Identifiant de la relecture dont la réponse est en cours de rédaction
    'replyingTo' => null,
])

{{-- Traitement d'un retour en un clic, réponse facultative (trait ResolvesReviews). --}}
@php
    $open = in_array($review->status, ['pending_admin', 'in_progress'], true);
    $replying = $replyingTo === $review->id;
    $actions = [
        'done' => ['label' => '✓ Réglé', 'class' => 'text-green-800 bg-green-100 hover:bg-green-200 dark:text-green-200 dark:bg-green-900/40 dark:hover:bg-green-900/60 border-transparent'],
        'in_progress' => ['label' => 'Je m\'en occupe', 'class' => 'text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'],
        'revision_requested' => ['label' => '↺ Renvoyer au relecteur', 'class' => 'text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'],
    ];
    unset($actions[$review->status]);
@endphp

<div class="flex flex-col gap-2" wire:key="review-actions-{{ $review->id }}">
    @if ($replying)
        <div>
            <label for="reply-{{ $review->id }}" class="text-sm font-medium text-gray-900 dark:text-white">
                Réponse au relecteur
                <span class="font-normal text-gray-500 dark:text-gray-400">· envoyée avec l'action choisie, visible dans son historique</span>
            </label>
            <textarea id="reply-{{ $review->id }}" wire:model="replies.{{ $review->id }}" rows="3" maxlength="5000"
                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white"
                placeholder="Ex : Merci, j'ai mis à jour la date du marché de Riez."></textarea>
            @if ($review->admin_response)
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Videz le champ pour retirer la réponse précédente (elle reste dans le journal).</p>
            @endif
        </div>
    @endif
    @error("replies.{$review->id}") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

    <div class="flex flex-wrap items-center gap-2">
        @if ($open || $replying)
            @foreach ($actions as $status => $action)
                <button type="button" wire:click="resolveReview({{ $review->id }}, '{{ $status }}')" wire:loading.attr="disabled"
                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md border {{ $action['class'] }}">
                    {{ $action['label'] }}
                </button>
            @endforeach
            @if ($replying)
                <button type="button" wire:click="cancelReply" class="px-2 py-1.5 text-xs text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    Annuler
                </button>
            @else
                <button type="button" wire:click="startReply({{ $review->id }})" class="px-2 py-1.5 text-xs text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    Répondre…
                </button>
            @endif
        @else
            {{-- Retour déjà réglé ou renvoyé : on peut revenir dessus, discrètement. --}}
            <button type="button" wire:click="startReply({{ $review->id }})"
                class="text-xs text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white underline underline-offset-2">
                Modifier le traitement…
            </button>
        @endif
    </div>
</div>
