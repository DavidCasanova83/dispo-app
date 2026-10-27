<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationEvent;
use App\Models\VerificationPage;

/**
 * Écrit le journal des pages à vérifier (table verification_events).
 *
 * Le journal garde ce que les tables métier écrasent ou retirent : version
 * précédente d'une relecture renvoyée, réponses de l'admin, relecteurs retirés,
 * clôtures. Les noms sont copiés dans `data` pour survivre à la suppression
 * d'un compte.
 */
class VerificationJournal
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(
        VerificationPage $page,
        string $type,
        array $data = [],
        ?int $userId = null,
        ?int $reviewId = null,
        ?int $actorId = null,
    ): VerificationEvent {
        $actorId ??= auth()->id();

        if ($userId && ! isset($data['user_name'])) {
            $data['user_name'] = User::whereKey($userId)->value('name');
        }
        if ($actorId && ! isset($data['actor_name'])) {
            $data['actor_name'] = User::whereKey($actorId)->value('name');
        }

        return VerificationEvent::create([
            'page_id' => $page->id,
            'cycle' => $page->cycle ?? 1,
            'type' => $type,
            'review_id' => $reviewId,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'data' => array_filter($data, fn ($v) => $v !== null),
        ]);
    }
}
