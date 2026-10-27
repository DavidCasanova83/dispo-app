<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationPage;
use App\Models\VerificationReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VerificationReviewService
{
    public function __construct(private readonly VerificationJournal $journal) {}

    public function submit(VerificationPage $page, User $user, array $data): VerificationReview
    {
        $language = in_array($data['language'] ?? 'fr', ['fr', 'en', 'it'], true) ? $data['language'] : 'fr';
        $contentOk = (bool) ($data['content_ok'] ?? false);
        $mediaOk = (bool) ($data['media_ok'] ?? false);
        $contentComment = trim($data['content_comment'] ?? '');
        $mediaComment = trim($data['media_comment'] ?? '');
        $remarks = trim($data['remarks'] ?? '');

        // Section 2 (évaluation éditoriale) — toutes optionnelles, pas d'impact sur le statut.
        $relevance = $data['relevance'] ?? null;
        $contentToAdd = $data['content_to_add'] ?? [];
        $contentToAdd = is_array($contentToAdd) ? array_values(array_filter($contentToAdd)) : [];
        $contentToAddDetails = trim($data['content_to_add_details'] ?? '');
        $contentToRemove = $data['content_to_remove'] ?? null;
        $contentToRemoveDetails = trim($data['content_to_remove_details'] ?? '');
        $visitorPerspective = $data['visitor_perspective'] ?? null;
        $visitorUnanswered = trim($data['visitor_unanswered'] ?? '');
        $rating = isset($data['rating']) && $data['rating'] !== '' ? (int) $data['rating'] : null;
        $suggestions = trim($data['suggestions'] ?? '');

        // All clear : seule la Section 1 décide du statut de la review.
        $allClear = $contentOk && $mediaOk;

        return DB::transaction(function () use (
            $page, $user, $language, $contentOk, $mediaOk, $contentComment, $mediaComment, $remarks,
            $relevance, $contentToAdd, $contentToAddDetails, $contentToRemove, $contentToRemoveDetails,
            $visitorPerspective, $visitorUnanswered, $rating, $suggestions, $allClear
        ) {
            // Verrou pessimiste sur la page : sérialise les soumissions concurrentes.
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->first();
            $cycle = $lockedPage->cycle ?? 1;

            $key = [
                'page_id' => $lockedPage->id,
                'user_id' => $user->id,
                'language' => $language,
                'cycle' => $cycle,
            ];

            // Une relecture renvoyée puis resoumise remplace la précédente : la
            // version précédente (et la réponse de l'admin) part au journal.
            $previous = VerificationReview::where($key)->first()?->snapshot();

            $review = VerificationReview::updateOrCreate($key, [
                'content_ok' => $contentOk,
                'content_comment' => $contentComment ?: null,
                'media_ok' => $mediaOk,
                'media_comment' => $mediaComment ?: null,
                'remarks' => $remarks ?: null,
                'relevance' => $relevance ?: null,
                'content_to_add' => empty($contentToAdd) ? null : $contentToAdd,
                'content_to_add_details' => $contentToAddDetails ?: null,
                'content_to_remove' => $contentToRemove ?: null,
                'content_to_remove_details' => $contentToRemoveDetails ?: null,
                'visitor_perspective' => $visitorPerspective ?: null,
                'visitor_unanswered' => $visitorUnanswered ?: null,
                'rating' => $rating,
                'suggestions' => $suggestions ?: null,
                'status' => $allClear ? 'done' : 'pending_admin',
                // Nouvelle soumission : la réponse précédente de l'admin ne s'applique
                // plus (elle reste dans le journal).
                'admin_response' => null,
                'admin_response_at' => null,
                // Le relecteur connaît l'état de ce qu'il vient d'envoyer.
                'reviewer_seen_at' => now(),
                'submitted_at' => now(),
            ]);

            $this->journal->record($lockedPage, 'review_submitted', [
                'language' => $language,
                'all_clear' => $allClear,
                'resubmitted' => $previous !== null,
                'previous' => $previous,
            ], $user->id, $review->id, $user->id);

            // Le statut de la page n'est piloté que par les reviews FR.
            if ($language === 'fr') {
                $this->recomputePageStatus($lockedPage);
            }

            return $review;
        });
    }

    /**
     * @param  string|null  $adminResponse  null : laisse le message existant intact.
     *                                      '' : efface le message existant.
     *                                      texte : remplace le message et l'horodate.
     */
    public function resolve(VerificationReview $review, string $newStatus, ?string $adminResponse = null): void
    {
        $allowed = ['in_progress', 'done', 'revision_requested'];
        if (! in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException("Statut invalide : {$newStatus}");
        }

        DB::transaction(function () use ($review, $newStatus, $adminResponse) {
            // Verrou pessimiste sur la page liée à la review.
            $lockedPage = VerificationPage::withTrashed()->whereKey($review->page_id)->lockForUpdate()->first();
            $review = VerificationReview::whereKey($review->id)->lockForUpdate()->firstOrFail();

            if ($review->isArchived()) {
                throw new \DomainException('Cette relecture appartient à un cycle clôturé : elle ne se traite plus.');
            }

            $payload = [
                'status' => $newStatus,
                // Nouveauté pour le relecteur : pastille jusqu'à ce qu'il l'ait vue.
                'reviewer_seen_at' => null,
            ];

            $message = null;
            if ($adminResponse !== null) {
                $message = trim($adminResponse);
                $payload['admin_response'] = $message !== '' ? $message : null;
                $payload['admin_response_at'] = $message !== '' ? now() : null;
            }

            $from = $review->status;
            $review->update($payload);

            $this->journal->record($lockedPage, 'review_resolved', [
                'language' => $review->language,
                'from' => $from,
                'to' => $newStatus,
                'message' => $message !== '' ? $message : null,
                'message_cleared' => $message === '' ? true : null,
            ], $review->user_id, $review->id);

            // Recalcule le statut page selon TOUTES les reviews FR actives,
            // pas uniquement celle qui vient d'être traitée.
            if ($review->language === 'fr') {
                $this->recomputePageStatus($lockedPage);
            }
        });
    }

    /**
     * Point d'entrée public du recalcul de statut, pour les cas où la page change
     * sans qu'une review soit soumise ou traitée — typiquement l'ajout ou le
     * retrait d'un relecteur, qui modifie le dénominateur du calcul.
     */
    public function refreshPageStatus(VerificationPage $page): void
    {
        DB::transaction(function () use ($page) {
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->first();

            if ($lockedPage) {
                $this->recomputePageStatus($lockedPage);
            }
        });
    }

    /**
     * Calcule et met à jour le statut d'une page selon la règle pessimiste :
     * une page n'est prête à être clôturée que si TOUS ses relecteurs assignés ont
     * une review FR active 'done'.
     *
     * Le calcul s'appuie sur le STATUT des reviews (l'avancement du traitement admin),
     * et non sur les drapeaux content_ok / media_ok (les réponses du relecteur).
     * Ces drapeaux sont figés jusqu'à une resoumission : s'y fier rendait 'in_progress'
     * inatteignable et bloquait définitivement en 'needs_fix' toute page dont le
     * problème avait pourtant été corrigé et la review marquée 'done'.
     *
     * Les reviews 'revision_requested' sont ignorées (l'admin a redemandé un nouveau passage).
     * Les reviews de relecteurs retirés de l'assignation ne pèsent plus dans le calcul.
     */
    private function recomputePageStatus(VerificationPage $page): void
    {
        $assigneeIds = $page->assignees()->pluck('users.id');

        if ($assigneeIds->isEmpty()) {
            $page->update(['status' => 'pending']);

            return;
        }

        $activeReviews = VerificationReview::query()
            ->where('page_id', $page->id)
            ->whereNull('archived_at')
            ->where('language', 'fr')
            ->whereIn('user_id', $assigneeIds)
            ->where('status', '!=', 'revision_requested')
            ->get();

        if ($activeReviews->isEmpty()) {
            $page->update(['status' => 'pending']);

            return;
        }

        // Un problème est « ouvert » tant que l'admin ne l'a pas pris en charge.
        $hasUntreated = $activeReviews->contains(fn ($r) => $r->status === 'pending_admin');
        $hasInProgress = $activeReviews->contains(fn ($r) => $r->status === 'in_progress');
        $allDone = $activeReviews->every(fn ($r) => $r->status === 'done');
        $reviewedAssignees = $activeReviews->pluck('user_id')->unique();
        $allAssigneesReviewed = $reviewedAssignees->count() === $assigneeIds->count();

        if ($hasUntreated) {
            $newStatus = 'needs_fix';
        } elseif ($hasInProgress) {
            $newStatus = 'in_progress';
        } elseif ($allDone && $allAssigneesReviewed) {
            // Toutes les reviews FR sont 'done' sans problème : la page est prête à être clôturée.
            // Le passage en 'validated' est désormais une action manuelle de l'admin
            // (closeAnnualVerification()), il n'est plus automatique.
            $newStatus = 'awaiting_validation';
        } else {
            $newStatus = 'pending';
        }

        $page->update(['status' => $newStatus]);
    }

    /**
     * Clôture la vérification annuelle d'une page :
     * - archive les relectures du cycle (toutes langues) : elles restent en base,
     *   consultables dans la fiche de la page et l'historique des relecteurs ;
     * - passe la page au cycle suivant, en 'validated', et horodate validated_at ;
     * - conserve les assignations (les mêmes relecteurs reverront la page dans 1 an).
     *
     * Rien n'est supprimé. À n'utiliser que sur une page en 'awaiting_validation'.
     *
     * @return int Nombre de relectures archivées
     */
    public function closeAnnualVerification(VerificationPage $page): int
    {
        return DB::transaction(function () use ($page) {
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->first();
            $cycle = $lockedPage->cycle ?? 1;

            // toBase() : l'archivage ne doit pas changer updated_at (date du traitement).
            $archived = VerificationReview::where('page_id', $lockedPage->id)
                ->whereNull('archived_at')
                ->toBase()
                ->update(['archived_at' => now()]);

            // Journal avant le changement de cycle : l'événement appartient au cycle clôturé.
            $this->journal->record($lockedPage, 'closed', ['cycle' => $cycle, 'archived' => $archived]);

            $lockedPage->update([
                'status' => 'validated',
                'validated_at' => now(),
                'cycle' => $cycle + 1,
            ]);

            return $archived;
        });
    }

    /**
     * Clôture en masse. Seules les pages « prêtes à clôturer » le sont ; les
     * autres sont ignorées (leur statut a pu changer depuis l'affichage).
     *
     * @param  int[]  $pageIds
     * @return array{closed: int, skipped: int, archived: int}
     */
    public function closeMany(array $pageIds): array
    {
        $pages = VerificationPage::whereIn('id', $pageIds)->get();
        $ready = $pages->where('status', 'awaiting_validation');

        $archived = 0;
        foreach ($ready as $page) {
            $archived += $this->closeAnnualVerification($page);
        }

        return [
            'closed' => $ready->count(),
            'skipped' => count(array_unique($pageIds)) - $ready->count(),
            'archived' => $archived,
        ];
    }

    public function pendingForUser(User $user): Collection
    {
        return VerificationPage::pendingForUser($user)
            ->orderByRaw(VerificationPage::priorityOrderSql())
            ->orderBy('deadline')
            ->get();
    }

    public function historyForUser(User $user, array $filters = []): Builder
    {
        $query = VerificationReview::query()
            ->with('page')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['period'])) {
            if ($filters['period'] === 'this_year') {
                $query->whereYear('created_at', now()->year);
            } elseif ($filters['period'] === 'this_month') {
                $query->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
            }
        }

        return $query;
    }

    public function userVerificationCount(User $user): int
    {
        return VerificationReview::where('user_id', $user->id)->count();
    }

    /** Réponses ou traitements de l'admin pas encore vus par le relecteur. */
    public function unseenResponsesCount(User $user): int
    {
        return VerificationReview::where('user_id', $user->id)
            ->unseenByReviewer()
            ->whereHas('page', fn ($q) => $q->whereNull('verification_pages.deleted_at'))
            ->count();
    }

    /**
     * @param  int[]|null  $reviewIds  null : toutes les relectures du relecteur.
     */
    public function markResponsesSeen(User $user, ?array $reviewIds = null): void
    {
        // toBase() : « vu » ne doit pas changer updated_at (date du traitement).
        VerificationReview::where('user_id', $user->id)
            ->unseenByReviewer()
            ->when($reviewIds !== null, fn ($q) => $q->whereIn('id', $reviewIds))
            ->toBase()
            ->update(['reviewer_seen_at' => now()]);
    }
}
