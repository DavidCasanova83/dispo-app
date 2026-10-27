<?php

namespace App\Services;

use App\Models\VerificationPage;
use App\Models\VerificationReview;

/**
 * Compteurs de l'espace « Vérification des pages » : bandeau des étapes,
 * onglets et pastille du menu. Chaque compteur correspond exactement aux
 * lignes que l'écran concerné affiche.
 */
class VerificationOverviewService
{
    /**
     * Nombre de pages par étape (VerificationPage::STAGES), en une requête.
     *
     * @return array<string, int>
     */
    public function stageCounts(): array
    {
        $counts = VerificationPage::query()
            ->selectRaw(VerificationPage::stageSql().' as stage, COUNT(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage');

        return collect(VerificationPage::STAGES)
            ->map(fn ($def, $stage) => (int) ($counts[$stage] ?? 0))
            ->all();
    }

    /** Retours reçus que l'admin n'a pas encore traités (pastille du menu). */
    public function newFeedbackCount(): int
    {
        return $this->activeReviewsWithStatus('pending_admin')->count();
    }

    /**
     * @return array{to_do: int, pages: int, verified: int, trash: int, out_of_site: int}
     */
    public function tabCounts(): array
    {
        return [
            'to_do' => $this->activeReviewsWithStatus('pending_admin')->count()
                + $this->activeReviewsWithStatus('in_progress')->count()
                + VerificationPage::where('status', 'awaiting_validation')->count(),
            'pages' => VerificationPage::count(),
            'verified' => VerificationPage::where('status', 'validated')->count(),
            'trash' => VerificationPage::onlyTrashed()->count(),
            'out_of_site' => VerificationPage::outOfSite()->count(),
        ];
    }

    /**
     * Relectures du cycle en cours dans un statut donné, sur des pages suivies
     * (hors corbeille).
     */
    public function activeReviewsWithStatus(string $status)
    {
        return VerificationReview::query()
            ->active()
            ->where('status', $status)
            ->whereHas('page', fn ($q) => $q->whereNull('verification_pages.deleted_at'));
    }
}
