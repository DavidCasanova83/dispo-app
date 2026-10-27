<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationPage;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des pages à vérifier côté admin : création, relecteurs, retrait du
 * suivi et restauration. Chaque action est inscrite au journal de la page.
 *
 * Rien n'est supprimé définitivement : une page retirée du suivi passe en
 * corbeille (SoftDeletes) avec ses relectures et ses assignations ; un
 * relecteur retiré garde ses relectures, et son assignation est copiée au
 * journal avant d'être enlevée.
 */
class VerificationPageService
{
    public function __construct(
        private readonly VerificationJournal $journal,
        private readonly VerificationReviewService $reviews,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  int[]  $assigneeIds
     */
    public function create(array $attributes, array $assigneeIds = []): VerificationPage
    {
        return DB::transaction(function () use ($attributes, $assigneeIds) {
            $page = VerificationPage::create($attributes + ['created_by' => auth()->id()]);
            $this->journal->record($page, 'created', ['source' => 'manuel']);
            $this->syncAssignees($page, $assigneeIds);

            return $page;
        });
    }

    /**
     * Remplace la liste des relecteurs d'une page sans réinitialiser la date
     * d'assignation ni la distribution des relecteurs déjà associés (la date
     * d'assignation pilote l'ordre de la distribution nocturne). Les nouveaux
     * arrivent en file d'attente.
     *
     * @param  int[]  $userIds
     */
    public function syncAssignees(VerificationPage $page, array $userIds): void
    {
        $userIds = $this->normalizeIds($userIds);
        $existing = DB::table('verification_assignments')
            ->where('page_id', $page->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->addAssignees([$page->id], array_values(array_diff($userIds, $existing)));
        $this->removeAssignees([$page->id], array_values(array_diff($existing, $userIds)));
    }

    /**
     * Ajoute des relecteurs (sans retirer ceux déjà en place).
     *
     * @param  int[]  $pageIds
     * @param  int[]  $userIds
     * @return int Nombre d'assignations créées
     */
    public function addAssignees(array $pageIds, array $userIds): int
    {
        $pageIds = $this->normalizeIds($pageIds);
        $userIds = $this->normalizeIds($userIds);

        if ($pageIds === [] || $userIds === []) {
            return 0;
        }

        return DB::transaction(function () use ($pageIds, $userIds) {
            $existing = DB::table('verification_assignments')
                ->whereIn('page_id', $pageIds)
                ->whereIn('user_id', $userIds)
                ->get(['page_id', 'user_id'])
                ->map(fn ($r) => $r->page_id.'-'.$r->user_id)
                ->flip();

            $now = now();
            $created = 0;

            foreach (VerificationPage::whereIn('id', $pageIds)->get() as $page) {
                $added = false;
                foreach ($userIds as $userId) {
                    if ($existing->has($page->id.'-'.$userId)) {
                        continue;
                    }
                    DB::table('verification_assignments')->insert([
                        'page_id' => $page->id,
                        'user_id' => $userId,
                        'released_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $this->journal->record($page, 'assigned', [], $userId);
                    $created++;
                    $added = true;
                }

                // Un relecteur de plus change le calcul : une page prête à
                // clôturer redevient en relecture.
                if ($added) {
                    $this->reviews->refreshPageStatus($page);
                }
            }

            return $created;
        });
    }

    /**
     * Retire des relecteurs. Leurs relectures restent en base ; l'assignation
     * (dates d'assignation et de distribution) est copiée au journal.
     *
     * @param  int[]  $pageIds
     * @param  int[]  $userIds
     * @return int Nombre d'assignations retirées
     */
    public function removeAssignees(array $pageIds, array $userIds): int
    {
        $pageIds = $this->normalizeIds($pageIds);
        $userIds = $this->normalizeIds($userIds);

        if ($pageIds === [] || $userIds === []) {
            return 0;
        }

        return DB::transaction(function () use ($pageIds, $userIds) {
            $rows = DB::table('verification_assignments')
                ->whereIn('page_id', $pageIds)
                ->whereIn('user_id', $userIds)
                ->get();

            if ($rows->isEmpty()) {
                return 0;
            }

            $pages = VerificationPage::withTrashed()->whereIn('id', $rows->pluck('page_id')->unique())->get()->keyBy('id');

            foreach ($rows as $row) {
                $this->journal->record($pages[$row->page_id], 'unassigned', [
                    'assigned_at' => $row->created_at,
                    'released_at' => $row->released_at,
                ], (int) $row->user_id);
            }

            DB::table('verification_assignments')->whereIn('id', $rows->pluck('id'))->delete();

            foreach ($pages as $page) {
                $this->reviews->refreshPageStatus($page);
            }

            return $rows->count();
        });
    }

    /**
     * Retire des pages du suivi (corbeille). Relectures, assignations et
     * journal sont conservés ; la page se restaure depuis la corbeille.
     *
     * @param  int[]  $pageIds
     */
    public function remove(array $pageIds): int
    {
        $pages = VerificationPage::whereIn('id', $this->normalizeIds($pageIds))->get();

        DB::transaction(function () use ($pages) {
            foreach ($pages as $page) {
                $this->journal->record($page, 'removed');
                $page->delete();
            }
        });

        return $pages->count();
    }

    /** Retire du suivi toutes les pages qui ne sont plus sur le site. */
    public function removeOutOfSite(): int
    {
        return $this->remove(VerificationPage::outOfSite()->pluck('id')->all());
    }

    public function restore(int $pageId): VerificationPage
    {
        return DB::transaction(function () use ($pageId) {
            $page = VerificationPage::onlyTrashed()->findOrFail($pageId);
            $page->restore();
            $this->journal->record($page, 'restored');

            return $page;
        });
    }

    /**
     * Relecteurs proposés dans les listes : comptes approuvés.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function availableReviewers()
    {
        return User::where('approved', true)->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }
}
