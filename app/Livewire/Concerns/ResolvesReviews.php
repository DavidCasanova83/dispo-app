<?php

namespace App\Livewire\Concerns;

use App\Models\VerificationReview;
use App\Services\VerificationReviewService;

/**
 * Traitement d'un retour de relecteur en un clic (Réglé, Je m'en occupe,
 * Renvoyer au relecteur), avec une réponse facultative rédigée sur place.
 * Utilisé par l'onglet « À traiter » et la fiche d'une page.
 */
trait ResolvesReviews
{
    /** @var array<int, string> Réponse en cours de rédaction, par relecture */
    public array $replies = [];

    public ?int $replyingTo = null;

    public const RESOLVE_MESSAGES = [
        'done' => 'Retour marqué réglé.',
        'in_progress' => 'Retour pris en charge : la page passe en correction.',
        'revision_requested' => 'Retour renvoyé au relecteur : la page réapparaît sur son tableau de bord.',
    ];

    public function startReply(int $reviewId): void
    {
        $review = VerificationReview::findOrFail($reviewId);
        $this->replyingTo = $review->id;
        $this->replies[$review->id] = $review->admin_response ?? '';
        $this->resetErrorBag();
    }

    public function cancelReply(): void
    {
        if ($this->replyingTo !== null) {
            unset($this->replies[$this->replyingTo]);
        }
        $this->replyingTo = null;
        $this->resetErrorBag();
    }

    public function resolveReview(int $reviewId, string $status): void
    {
        abort_unless(isset(self::RESOLVE_MESSAGES[$status]), 422);

        $this->validate(
            ["replies.{$reviewId}" => ['nullable', 'string', 'max:5000']],
            ["replies.{$reviewId}.max" => 'La réponse ne doit pas dépasser 5000 caractères.'],
        );

        $review = VerificationReview::findOrFail($reviewId);

        // Sans réponse en cours de rédaction, le message existant reste tel quel.
        $message = $this->replyingTo === $review->id ? (string) ($this->replies[$review->id] ?? '') : null;

        try {
            app(VerificationReviewService::class)->resolve($review, $status, $message);
        } catch (\DomainException $e) {
            $this->addError("replies.{$reviewId}", $e->getMessage());

            return;
        }

        unset($this->replies[$review->id]);
        $this->replyingTo = null;

        session()->flash('success', self::RESOLVE_MESSAGES[$status].($message ? ' Votre réponse est visible par le relecteur.' : ''));
    }
}
