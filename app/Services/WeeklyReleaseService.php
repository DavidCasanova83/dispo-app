<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WeeklyReleaseService
{
    public const WEEKLY_QUOTA = 2;

    /**
     * Pour chaque utilisateur ayant des pages assignées en attente, libère
     * autant de pages que nécessaire pour atteindre WEEKLY_QUOTA "actives".
     *
     * Une page "active" pour un user = assignation released ET review FR pas done.
     * Si le user a déjà ≥ WEEKLY_QUOTA actives, on ne libère rien (plafond strict).
     *
     * @return array<int,int> id_user => nb pages libérées
     */
    public function releaseWeekly(): array
    {
        $userIds = DB::table('verification_assignments')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        $report = [];

        foreach ($userIds as $userId) {
            $released = $this->releaseForUserUpToQuota((int) $userId);
            if ($released > 0) {
                $report[(int) $userId] = $released;
            }
        }

        return $report;
    }

    /**
     * Libère pour un user jusqu'à atteindre le quota hebdomadaire.
     * Sélection : priority haute → moyenne → basse, puis deadline la plus proche,
     * puis date d'assignation la plus ancienne.
     *
     * @return int Nombre de pages effectivement libérées
     */
    public function releaseForUserUpToQuota(int $userId): int
    {
        $activeCount = $this->countActiveForUser($userId);

        if ($activeCount >= self::WEEKLY_QUOTA) {
            return 0;
        }

        $needed = self::WEEKLY_QUOTA - $activeCount;

        // IDs d'assignations en attente (released_at NULL) pour cet user, triées.
        $candidateIds = DB::table('verification_assignments as va')
            ->join('verification_pages as vp', 'va.page_id', '=', 'vp.id')
            ->where('va.user_id', $userId)
            ->whereNull('va.released_at')
            ->orderByRaw("FIELD(vp.priority, 'high', 'medium', 'low')")
            ->orderByRaw('vp.deadline IS NULL, vp.deadline ASC')
            ->orderBy('va.created_at')
            ->limit($needed)
            ->pluck('va.id');

        if ($candidateIds->isEmpty()) {
            return 0;
        }

        DB::table('verification_assignments')
            ->whereIn('id', $candidateIds)
            ->update(['released_at' => now()]);

        return $candidateIds->count();
    }

    /**
     * Libère immédiatement la page pour TOUS les relecteurs assignés qui n'ont pas
     * encore reçu cette page. Utilisé par le bouton "Libérer maintenant" admin.
     *
     * Comportement : ajoute (peut faire dépasser le quota de 2 pour la semaine).
     *
     * @return int Nombre de relecteurs nouvellement libérés sur cette page
     */
    public function releasePageNow(int $pageId): int
    {
        return DB::table('verification_assignments')
            ->where('page_id', $pageId)
            ->whereNull('released_at')
            ->update(['released_at' => now()]);
    }

    /**
     * Compte les pages "actives" pour un user :
     * assignation libérée (released_at NOT NULL)
     * ET statut page != 'validated'
     * ET pas de review FR clôturée du user (done / pending_admin / in_progress).
     *
     * Aligné sur VerificationPage::scopePendingForUser : une page n'est "active"
     * que si l'utilisateur a encore quelque chose à faire dessus (aucune review FR
     * OU review en 'revision_requested'). Dès qu'il a soumis et que la review
     * est entre les mains de l'admin, on libère son quota — il n'a plus à attendre
     * pour recevoir une nouvelle page.
     */
    public function countActiveForUser(int $userId): int
    {
        return DB::table('verification_assignments as va')
            ->join('verification_pages as vp', 'va.page_id', '=', 'vp.id')
            ->where('va.user_id', $userId)
            ->whereNotNull('va.released_at')
            ->where('vp.status', '!=', 'validated')
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('verification_reviews')
                    ->whereColumn('verification_reviews.page_id', 'va.page_id')
                    ->where('verification_reviews.user_id', $userId)
                    ->where('verification_reviews.language', 'fr')
                    ->whereIn('verification_reviews.status', ['done', 'pending_admin', 'in_progress']);
            })
            ->count();
    }
}
