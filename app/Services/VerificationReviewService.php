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

            $review = VerificationReview::updateOrCreate(
                [
                    'page_id' => $lockedPage->id,
                    'user_id' => $user->id,
                    'language' => $language,
                ],
                [
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
                    // On efface une éventuelle réponse admin précédente : c'est une nouvelle soumission.
                    'admin_response' => null,
                    'admin_response_at' => null,
                ]
            );

            // Le statut de la page n'est piloté que par les reviews FR.
            // Les reviews EN/IT sont des bonus, n'impactent pas le cycle de vie de la page.
            if ($language === 'fr') {
                $this->recomputePageStatus($lockedPage);
            }

            return $review;
        });
    }

    public function resolve(VerificationReview $review, string $newStatus, ?string $adminResponse = null): void
    {
        $allowed = ['in_progress', 'done', 'revision_requested'];
        if (! in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException("Statut invalide : {$newStatus}");
        }

        DB::transaction(function () use ($review, $newStatus, $adminResponse) {
            // Verrou pessimiste sur la page liée à la review.
            $lockedPage = VerificationPage::whereKey($review->page_id)->lockForUpdate()->first();

            $review->update([
                'status' => $newStatus,
                'admin_response' => $adminResponse ?: $review->admin_response,
                'admin_response_at' => $adminResponse ? now() : $review->admin_response_at,
            ]);

            // Recalcule le statut page selon TOUTES les reviews FR actives,
            // pas uniquement celle qui vient d'être traitée.
            if ($review->language === 'fr') {
                $this->recomputePageStatus($lockedPage);
            }
        });
    }

    /**
     * Calcule et met à jour le statut d'une page selon la règle pessimiste :
     * une page n'est validated que si TOUS ses relecteurs assignés ont une review FR active 'done',
     * et qu'AUCUNE review FR active ne signale de problème.
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
            ->where('language', 'fr')
            ->whereIn('user_id', $assigneeIds)
            ->where('status', '!=', 'revision_requested')
            ->get();

        if ($activeReviews->isEmpty()) {
            $page->update(['status' => 'pending']);
            return;
        }

        $hasIssue = $activeReviews->contains(fn ($r) => ! $r->content_ok || ! $r->media_ok);
        $hasInProgress = $activeReviews->contains(fn ($r) => in_array($r->status, ['pending_admin', 'in_progress'], true));
        $allDone = $activeReviews->every(fn ($r) => $r->status === 'done');
        $reviewedAssignees = $activeReviews->pluck('user_id')->unique();
        $allAssigneesReviewed = $reviewedAssignees->count() === $assigneeIds->count();

        if ($hasIssue) {
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
     * - supprime TOUTES les reviews (toutes langues confondues) ;
     * - passe la page en 'validated' et horodate validated_at ;
     * - conserve les assignations (les mêmes relecteurs reverront la page dans 1 an).
     *
     * Action irréversible : à n'utiliser que sur une page en 'awaiting_validation'.
     */
    public function closeAnnualVerification(VerificationPage $page): void
    {
        DB::transaction(function () use ($page) {
            $lockedPage = VerificationPage::whereKey($page->id)->lockForUpdate()->first();

            VerificationReview::where('page_id', $lockedPage->id)->delete();

            $lockedPage->update([
                'status' => 'validated',
                'validated_at' => now(),
            ]);
        });
    }

    public function pendingForUser(User $user): Collection
    {
        return VerificationPage::pendingForUser($user)
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
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
}
