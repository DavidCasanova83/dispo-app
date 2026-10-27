<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VerificationPage;
use Illuminate\Support\Facades\DB;

/**
 * Distribution des pages assignées vers les dashboards des relecteurs.
 *
 * Le quota n'est pas une allocation périodique mais un PLAFOND DE PAGES ACTIVES
 * SIMULTANÉES : un relecteur n'a jamais plus de quota() pages ouvertes en même
 * temps (réglable dans « Vérification des pages », 2 par défaut). La fréquence d'exécution ne change donc pas cette limite, elle
 * change seulement la vitesse à laquelle une page terminée est remplacée.
 *
 * Cadence courante : chaque nuit (voir routes/console.php).
 */
class PageReleaseService
{
    /** Plafond par défaut de pages actives simultanément par relecteur. */
    public const ACTIVE_PAGES_QUOTA = 2;

    /** Bornes du réglage. */
    public const QUOTA_MIN = 1;

    public const QUOTA_MAX = 20;

    public const QUOTA_SETTING = 'verification.active_pages_quota';

    public function __construct(private readonly VerificationJournal $journal) {}

    /** Plafond en vigueur : le réglage de l'écran, sinon ACTIVE_PAGES_QUOTA. */
    public static function quota(): int
    {
        $value = (int) Setting::get(self::QUOTA_SETTING, self::ACTIVE_PAGES_QUOTA);

        return max(self::QUOTA_MIN, min(self::QUOTA_MAX, $value));
    }

    public static function setQuota(int $quota): int
    {
        $quota = max(self::QUOTA_MIN, min(self::QUOTA_MAX, $quota));
        Setting::set(self::QUOTA_SETTING, (string) $quota);

        return $quota;
    }

    /**
     * Pour chaque utilisateur ayant des pages assignées en attente, libère
     * autant de pages que nécessaire pour atteindre quota() "actives".
     *
     * Une page "active" pour un user = assignation released ET review FR pas done.
     * Si le user a déjà ≥ quota() actives, on ne libère rien.
     *
     * @return array<int,int> id_user => nb pages libérées
     */
    public function replenishAll(): array
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
     * Libère pour un user jusqu'à atteindre le plafond de pages actives.
     * Sélection : priority haute → moyenne → basse, puis deadline la plus proche,
     * puis date d'assignation la plus ancienne.
     *
     * @return int Nombre de pages effectivement libérées
     */
    public function releaseForUserUpToQuota(int $userId): int
    {
        $quota = self::quota();
        $activeCount = $this->countActiveForUser($userId);

        if ($activeCount >= $quota) {
            return 0;
        }

        $needed = $quota - $activeCount;

        // Assignations en attente (released_at NULL) pour cet user, triées.
        $candidates = DB::table('verification_assignments as va')
            ->join('verification_pages as vp', 'va.page_id', '=', 'vp.id')
            ->where('va.user_id', $userId)
            ->whereNull('va.released_at')
            ->whereNull('vp.deleted_at')
            ->orderByRaw(VerificationPage::priorityOrderSql('vp.priority'))
            ->orderByRaw('vp.deadline IS NULL, vp.deadline ASC')
            ->orderBy('va.created_at')
            ->limit($needed)
            ->pluck('va.page_id', 'va.id');

        if ($candidates->isEmpty()) {
            return 0;
        }

        DB::table('verification_assignments')
            ->whereIn('id', $candidates->keys())
            ->update(['released_at' => now()]);

        $this->logDistribution($candidates->values()->all(), $userId, 'nuit');

        return $candidates->count();
    }

    /**
     * Libère immédiatement la page pour TOUS les relecteurs assignés qui n'ont pas
     * encore reçu cette page. Utilisé par le bouton "Libérer maintenant" admin.
     *
     * Comportement : ajoute (peut faire dépasser le plafond de pages actives).
     *
     * @return int Nombre de relecteurs nouvellement libérés sur cette page
     */
    public function releasePageNow(int $pageId, ?int $userId = null): int
    {
        $userIds = DB::table('verification_assignments')
            ->where('page_id', $pageId)
            ->whereNull('released_at')
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        DB::table('verification_assignments')
            ->where('page_id', $pageId)
            ->whereIn('user_id', $userIds)
            ->whereNull('released_at')
            ->update(['released_at' => now()]);

        foreach ($userIds as $uid) {
            $this->logDistribution([$pageId], (int) $uid, 'manuel');
        }

        return $userIds->count();
    }

    /**
     * @param  int[]  $pageIds
     */
    private function logDistribution(array $pageIds, int $userId, string $mode): void
    {
        VerificationPage::whereIn('id', $pageIds)->get()->each(
            fn (VerificationPage $page) => $this->journal->record($page, 'distributed', ['mode' => $mode], $userId)
        );
    }

    /**
     * Compte les pages "actives" pour un user :
     * assignation libérée (released_at NOT NULL)
     * ET page non supprimée
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
            ->whereNull('vp.deleted_at')
            ->where('vp.status', '!=', 'validated')
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('verification_reviews')
                    ->whereColumn('verification_reviews.page_id', 'va.page_id')
                    ->where('verification_reviews.user_id', $userId)
                    ->where('verification_reviews.language', 'fr')
                    ->whereNull('verification_reviews.archived_at')
                    ->whereIn('verification_reviews.status', ['done', 'pending_admin', 'in_progress']);
            })
            ->count();
    }
}
