<?php

namespace App\Livewire\Verification\Admin;

use App\Livewire\Concerns\WithSorting;
use App\Models\User;
use App\Models\VerificationPage;
use App\Services\SitemapScanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PagesManager extends Component
{
    use WithPagination;
    use WithSorting;

    public string $search = '';
    public string $filterStatus = 'all';
    public string $filterPriority = 'all';
    public string $filterCategory = 'all';
    public string $filterAssignment = 'all'; // all | with | without
    // Non typé volontairement : la valeur est hydratée depuis le queryString, où
    // n'importe quoi peut arriver. Un typage ?int ferait planter l'hydratation
    // sur une valeur non numérique. La normalisation se fait dans reviewerId().
    public $filterReviewer = null;
    public string $filterSitemap = 'all';    // all | in | out
    public bool $filterOverdue = false;
    public int $perPage = 25;

    // Form modal (create / edit)
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $url = '';
    public ?string $urlEn = null;
    public ?string $urlIt = null;
    public ?string $theme = '';
    public ?string $category = null;
    public string $priority = 'medium';
    public ?string $deadline = null;
    public array $assigneeIds = [];

    // Quick assign modal
    public bool $showAssignModal = false;
    public ?int $assigningPageId = null;
    public array $quickAssigneeIds = [];

    // Release now modal
    public bool $showReleaseModal = false;
    public ?int $releasingPageId = null;
    public ?string $releasingPageTitle = null;
    public int $releasingQueuedCount = 0;

    // Sélection en masse
    public array $selectedPageIds = [];
    public bool $showBulkAssignModal = false;
    public array $bulkAssigneeIds = [];
    public bool $showBulkPriorityModal = false;
    public string $bulkPriority = 'medium';

    // Delete modal
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // Scan result banner
    public ?array $scanResult = null;
    public ?string $scanError = null;

    // Reset sitemap modal
    public bool $showResetModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'filterPriority' => ['except' => 'all'],
        'filterCategory' => ['except' => 'all'],
        'filterAssignment' => ['except' => 'all'],
        'filterReviewer' => ['as' => 'relecteur', 'except' => null],
        'filterSitemap' => ['except' => 'all'],
        'filterOverdue' => ['except' => false],
        'perPage' => ['except' => 25],
    ];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'urlEn' => 'nullable|url|max:500',
            'urlIt' => 'nullable|url|max:500',
            'theme' => 'nullable|string|max:100',
            'category' => 'nullable|in:' . implode(',', array_keys(VerificationPage::CATEGORIES)),
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'nullable|date',
            'assigneeIds' => 'array',
            'assigneeIds.*' => 'exists:users,id',
        ];
    }

    protected $messages = [
        'title.required' => 'Le titre est obligatoire.',
        'title.max' => 'Le titre ne doit pas dépasser 255 caractères.',
        'url.required' => 'L\'URL FR est obligatoire.',
        'url.url' => 'L\'URL FR doit être une adresse web valide (http:// ou https://).',
        'url.max' => 'L\'URL FR ne doit pas dépasser 500 caractères.',
        'urlEn.url' => 'L\'URL EN doit être une adresse web valide.',
        'urlEn.max' => 'L\'URL EN ne doit pas dépasser 500 caractères.',
        'urlIt.url' => 'L\'URL IT doit être une adresse web valide.',
        'urlIt.max' => 'L\'URL IT ne doit pas dépasser 500 caractères.',
        'priority.in' => 'La priorité doit être basse, moyenne ou haute.',
        'deadline.date' => 'La date limite n\'est pas valide.',
        'category.in' => 'Catégorie invalide.',
    ];

    // ─── TRI ──────────────────────────────────────────────────────

    /**
     * @return array<string, string|callable>
     */
    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'priority' => fn (Builder $q, string $dir) => $q->orderByRaw("FIELD(priority, 'high', 'medium', 'low') {$dir}"),
            'deadline' => fn (Builder $q, string $dir) => $q->orderByRaw("deadline IS NULL, deadline {$dir}"),
            'status' => fn (Builder $q, string $dir) => $q->orderByRaw(
                "FIELD(status, 'pending', 'in_progress', 'needs_fix', 'awaiting_validation', 'validated') {$dir}"
            ),
            'assignees' => fn (Builder $q, string $dir) => $q->orderBy('assignees_count', $dir),
            'created_at' => 'created_at',
        ];
    }

    protected function descendingFirstFields(): array
    {
        return ['created_at'];
    }

    protected function applyDefaultSorting(Builder $query): Builder
    {
        return $query
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'needs_fix', 'awaiting_validation', 'validated')")
            ->orderByDesc('created_at');
    }

    // ─── FILTRES ──────────────────────────────────────────────────

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterPriority()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingFilterAssignment()
    {
        $this->resetPage();
    }

    public function updatingFilterReviewer()
    {
        $this->resetPage();
    }

    /**
     * Identifiant du relecteur filtré, ou null si le paramètre est vide ou invalide.
     */
    private function reviewerId(): ?int
    {
        return is_numeric($this->filterReviewer) ? (int) $this->filterReviewer : null;
    }

    public function updatingFilterSitemap()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        // perPage vient du queryString : on le borne aux options proposées.
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 25;
        }
        $this->resetPage();
    }

    /**
     * Les tuiles de tête sont des filtres : un clic remplace la sélection
     * courante plutôt que de s'ajouter aux autres critères.
     */
    public function applyQuickFilter(string $key): void
    {
        $this->filterStatus = 'all';
        $this->filterAssignment = 'all';
        $this->filterOverdue = false;

        match ($key) {
            'pending' => $this->filterStatus = 'pending',
            'needs_fix' => $this->filterStatus = 'needs_fix',
            'awaiting_validation' => $this->filterStatus = 'awaiting_validation',
            'without_assignee' => $this->filterAssignment = 'without',
            'overdue' => $this->filterOverdue = true,
            default => null, // 'all' : on a déjà tout remis à zéro
        };

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterPriority = 'all';
        $this->filterCategory = 'all';
        $this->filterAssignment = 'all';
        $this->filterReviewer = null;
        $this->filterSitemap = 'all';
        $this->filterOverdue = false;
        $this->clearSorting();
    }

    public function activeQuickFilter(): string
    {
        if ($this->filterOverdue) {
            return 'overdue';
        }
        if ($this->filterAssignment === 'without') {
            return 'without_assignee';
        }
        if (in_array($this->filterStatus, ['pending', 'needs_fix', 'awaiting_validation'], true)) {
            return $this->filterStatus;
        }
        if ($this->filterStatus === 'all' && $this->filterAssignment === 'all') {
            return 'all';
        }

        return '';
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->filterStatus !== 'all'
            || $this->filterPriority !== 'all'
            || $this->filterCategory !== 'all'
            || $this->filterAssignment !== 'all'
            || $this->reviewerId() !== null
            || $this->filterSitemap !== 'all'
            || $this->filterOverdue
            || $this->sortField !== '';
    }

    // ─── SCAN SITEMAP ─────────────────────────────────────────────

    public function scanSitemap(SitemapScanService $service): void
    {
        $this->scanResult = null;
        $this->scanError = null;

        try {
            $this->scanResult = $service->scan(auth()->id());
        } catch (\Throwable $e) {
            $this->scanError = $e->getMessage();
        }
    }

    public function dismissScanResult(): void
    {
        $this->scanResult = null;
        $this->scanError = null;
    }

    public function openResetModal(): void
    {
        $this->showResetModal = true;
    }

    public function closeResetModal(): void
    {
        $this->showResetModal = false;
    }

    public function resetSitemapTracking(SitemapScanService $service): void
    {
        $count = $service->resetTracking();
        $this->showResetModal = false;
        $this->scanResult = null;
        $this->scanError = null;
        session()->flash('success', "État du sitemap réinitialisé pour {$count} page(s). Lancez un nouveau scan pour rafraîchir.");
    }

    // ─── FORM MODAL ───────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $pageId): void
    {
        $page = VerificationPage::with('assignees')->findOrFail($pageId);
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->url = $page->url;
        $this->urlEn = $page->url_en;
        $this->urlIt = $page->url_it;
        $this->theme = $page->theme;
        $this->category = $page->category;
        $this->priority = $page->priority;
        $this->deadline = $page->deadline?->toDateString();
        $this->assigneeIds = $page->assignees->pluck('id')->toArray();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'title' => $data['title'],
            'url' => $data['url'],
            'url_en' => $data['urlEn'] ?: null,
            'url_it' => $data['urlIt'] ?: null,
            'theme' => $data['theme'] ?: null,
            'category' => $data['category'] ?: null,
            'priority' => $data['priority'],
            'deadline' => $data['deadline'] ?: null,
        ];

        if ($this->editingId) {
            $page = VerificationPage::findOrFail($this->editingId);
            $page->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            $page = VerificationPage::create($payload);
        }

        $this->syncAssigneesPreservingReleased($page, $data['assigneeIds'] ?? []);

        session()->flash('success', $this->editingId ? 'Page mise à jour.' : 'Page créée.');
        $this->closeFormModal();
    }

    // ─── QUICK ASSIGN MODAL ───────────────────────────────────────

    public function openAssignModal(int $pageId): void
    {
        $page = VerificationPage::with('assignees')->findOrFail($pageId);
        $this->assigningPageId = $pageId;
        $this->quickAssigneeIds = $page->assignees->pluck('id')->toArray();
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->assigningPageId = null;
        $this->quickAssigneeIds = [];
    }

    public function saveAssignment(): void
    {
        if (! $this->assigningPageId) {
            return;
        }

        $page = VerificationPage::findOrFail($this->assigningPageId);
        $this->syncAssigneesPreservingReleased($page, $this->quickAssigneeIds);

        session()->flash('success', 'Relecteurs assignés.');
        $this->closeAssignModal();
    }

    // ─── RELEASE NOW MODAL ────────────────────────────────────────

    public function openReleaseModal(int $pageId): void
    {
        $page = VerificationPage::findOrFail($pageId);
        $this->releasingPageId = $pageId;
        $this->releasingPageTitle = $page->title;
        $this->releasingQueuedCount = DB::table('verification_assignments')
            ->where('page_id', $pageId)
            ->whereNull('released_at')
            ->count();
        $this->showReleaseModal = true;
    }

    public function closeReleaseModal(): void
    {
        $this->showReleaseModal = false;
        $this->releasingPageId = null;
        $this->releasingPageTitle = null;
        $this->releasingQueuedCount = 0;
    }

    public function releaseNow(\App\Services\PageReleaseService $service): void
    {
        if (! $this->releasingPageId) {
            return;
        }

        $count = $service->releasePageNow($this->releasingPageId);

        if ($count === 0) {
            session()->flash('success', 'Aucun relecteur en attente sur cette page : tout est déjà libéré.');
        } else {
            session()->flash('success', "Page libérée pour {$count} relecteur(s). Elle apparaîtra dès maintenant sur leur dashboard.");
        }

        $this->closeReleaseModal();
    }

    // ─── BULK ASSIGN ──────────────────────────────────────────────

    public function toggleSelectAll(bool $checked, array $idsOnPage): void
    {
        if ($checked) {
            $this->selectedPageIds = array_values(array_unique(array_merge($this->selectedPageIds, $idsOnPage)));
        } else {
            $this->selectedPageIds = array_values(array_diff($this->selectedPageIds, $idsOnPage));
        }
    }

    public function clearSelection(): void
    {
        $this->selectedPageIds = [];
    }

    public function openBulkAssignModal(): void
    {
        if (empty($this->selectedPageIds)) {
            return;
        }
        $this->bulkAssigneeIds = [];
        $this->showBulkAssignModal = true;
    }

    public function closeBulkAssignModal(): void
    {
        $this->showBulkAssignModal = false;
        $this->bulkAssigneeIds = [];
    }

    public function saveBulkAssignment(): void
    {
        if (empty($this->selectedPageIds) || empty($this->bulkAssigneeIds)) {
            session()->flash('success', 'Aucune action effectuée : sélectionnez au moins une page et un relecteur.');
            $this->closeBulkAssignModal();
            return;
        }

        $now = now();
        $insertCount = 0;

        // Récupère en une requête tous les couples (page_id, user_id) déjà existants
        // pour éviter les violations de l'unique (page_id, user_id) du pivot.
        $existingPairs = DB::table('verification_assignments')
            ->whereIn('page_id', $this->selectedPageIds)
            ->whereIn('user_id', $this->bulkAssigneeIds)
            ->select('page_id', 'user_id')
            ->get()
            ->map(fn ($r) => $r->page_id . '-' . $r->user_id)
            ->flip();

        $rows = [];
        foreach ($this->selectedPageIds as $pageId) {
            foreach ($this->bulkAssigneeIds as $userId) {
                if ($existingPairs->has($pageId . '-' . $userId)) {
                    continue; // assignation déjà en place, on ne touche pas (préserve released_at)
                }
                $rows[] = [
                    'page_id' => $pageId,
                    'user_id' => $userId,
                    'released_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('verification_assignments')->insert($rows);
            $insertCount = count($rows);
        }

        $pageCount = count($this->selectedPageIds);
        $userCount = count($this->bulkAssigneeIds);

        if ($insertCount === 0) {
            session()->flash('success', "Aucune nouvelle assignation : tous les relecteurs choisis étaient déjà assignés à toutes les pages sélectionnées.");
        } else {
            session()->flash('success', "{$insertCount} nouvelle(s) assignation(s) créée(s) sur {$pageCount} page(s) pour {$userCount} relecteur(s). Les pages seront distribuées cette nuit, au fil des places libres chez chaque relecteur (ou tout de suite via « Libérer maintenant »).");
        }

        $this->clearSelection();
        $this->closeBulkAssignModal();
    }

    public function openBulkPriorityModal(): void
    {
        if (empty($this->selectedPageIds)) {
            return;
        }
        $this->bulkPriority = 'medium';
        $this->showBulkPriorityModal = true;
    }

    public function closeBulkPriorityModal(): void
    {
        $this->showBulkPriorityModal = false;
        $this->bulkPriority = 'medium';
    }

    public function saveBulkPriority(): void
    {
        if (empty($this->selectedPageIds)) {
            $this->closeBulkPriorityModal();
            return;
        }

        if (! in_array($this->bulkPriority, ['low', 'medium', 'high'], true)) {
            session()->flash('success', 'Priorité invalide.');
            return;
        }

        $count = VerificationPage::whereIn('id', $this->selectedPageIds)
            ->update(['priority' => $this->bulkPriority]);

        $label = match ($this->bulkPriority) {
            'high' => 'Priorité haute',
            'medium' => 'Priorité moyenne',
            'low' => 'Priorité basse',
        };

        session()->flash('success', "{$count} page(s) mise(s) à jour : {$label}.");

        $this->clearSelection();
        $this->closeBulkPriorityModal();
    }

    /**
     * Synchronise les assignees d'une page sans réinitialiser le released_at
     * ni la date d'assignation des relecteurs déjà associés (cette date pilote
     * l'ordre FIFO de la distribution nocturne). Les nouveaux arrivent avec
     * released_at = NULL, en file d'attente jusqu'à la prochaine distribution
     * ou un release manuel.
     *
     * Le tout dans une transaction : un échec en cours de route ne doit pas
     * laisser la page sans relecteur.
     */
    private function syncAssigneesPreservingReleased(VerificationPage $page, array $userIds): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        DB::transaction(function () use ($page, $userIds) {
            $existing = DB::table('verification_assignments')
                ->where('page_id', $page->id)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $toRemove = array_diff($existing, $userIds);
            $toAdd = array_diff($userIds, $existing);

            if (! empty($toRemove)) {
                DB::table('verification_assignments')
                    ->where('page_id', $page->id)
                    ->whereIn('user_id', $toRemove)
                    ->delete();
            }

            if (! empty($toAdd)) {
                $now = now();
                DB::table('verification_assignments')->insert(
                    array_map(fn ($userId) => [
                        'page_id' => $page->id,
                        'user_id' => $userId,
                        'released_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], array_values($toAdd))
                );
            }

            // Retirer ou ajouter un relecteur change le résultat du calcul de statut
            // (une page peut redevenir incomplète, ou au contraire être prête).
            if (! empty($toRemove) || ! empty($toAdd)) {
                app(\App\Services\VerificationReviewService::class)->refreshPageStatus($page->fresh());
            }
        });
    }

    // ─── DELETE MODAL ─────────────────────────────────────────────

    public function openDeleteModal(int $pageId): void
    {
        $this->deletingId = $pageId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        VerificationPage::findOrFail($this->deletingId)->delete();
        session()->flash('success', 'Page supprimée.');
        $this->closeDeleteModal();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->url = '';
        $this->urlEn = null;
        $this->urlIt = null;
        $this->theme = '';
        $this->category = null;
        $this->priority = 'medium';
        $this->deadline = null;
        $this->assigneeIds = [];
        $this->resetErrorBag();
    }

    // ─── RENDER ───────────────────────────────────────────────────

    /**
     * Toutes les tuiles en une seule requête agrégée (auparavant 8 COUNT
     * distincts rejoués à chaque frappe dans la recherche).
     */
    private function stats(): array
    {
        $row = VerificationPage::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status = 'in_progress') as in_progress,
                SUM(status = 'needs_fix') as needs_fix,
                SUM(status = 'awaiting_validation') as awaiting_validation,
                SUM(status = 'validated') as validated,
                SUM(is_in_sitemap = 1) as in_sitemap,
                SUM(is_in_sitemap = 0 AND last_seen_in_sitemap_at IS NOT NULL) as orphan,
                SUM(status <> 'validated' AND deadline IS NOT NULL AND deadline < CURDATE()) as overdue,
                SUM(NOT EXISTS (
                    SELECT 1 FROM verification_assignments va WHERE va.page_id = verification_pages.id
                )) as without_assignee
            ")
            ->first();

        return array_map(
            fn ($v) => (int) $v,
            array_intersect_key((array) $row->getAttributes(), array_flip([
                'total', 'pending', 'in_progress', 'needs_fix', 'awaiting_validation',
                'validated', 'in_sitemap', 'orphan', 'overdue', 'without_assignee',
            ]))
        );
    }

    public function render()
    {
        $query = VerificationPage::query()
            ->with('assignees')
            ->withCount('assignees')
            ->withCount(['assignees as queued_count' => function ($q) {
                $q->whereNull('verification_assignments.released_at');
            }]);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%')
                    ->orWhere('theme', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPriority !== 'all') {
            $query->where('priority', $this->filterPriority);
        }

        if ($this->filterCategory !== 'all') {
            if ($this->filterCategory === 'none') {
                $query->whereNull('category');
            } else {
                $query->byCategory($this->filterCategory);
            }
        }

        if ($this->filterAssignment === 'without') {
            $query->withoutAssignee();
        } elseif ($this->filterAssignment === 'with') {
            $query->whereHas('assignees');
        }

        if ($reviewerId = $this->reviewerId()) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $reviewerId));
        }

        if ($this->filterSitemap === 'in') {
            $query->where('is_in_sitemap', true);
        } elseif ($this->filterSitemap === 'out') {
            $query->where('is_in_sitemap', false)->whereNotNull('last_seen_in_sitemap_at');
        }

        if ($this->filterOverdue) {
            $query->where('status', '!=', 'validated')
                ->whereNotNull('deadline')
                ->whereDate('deadline', '<', now()->toDateString());
        }

        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 25;

        $pages = $this->applySorting($query)->paginate($perPage);

        $availableUsers = User::where('approved', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.verification.admin.pages-manager', [
            'pages' => $pages,
            'availableUsers' => $availableUsers,
            'stats' => $this->stats(),
        ])->layout('components.layouts.app');
    }
}
