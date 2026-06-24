<?php

namespace App\Livewire\Verification\Admin;

use App\Models\User;
use App\Models\VerificationPage;
use App\Services\SitemapScanService;
use Livewire\Component;
use Livewire\WithPagination;

class PagesManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = 'all';
    public string $filterPriority = 'all';
    public string $filterCategory = 'all';
    public string $filterAssignment = 'all'; // all | with | without
    public int $perPage = 15;

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

    // Hard delete all modal
    public bool $showHardDeleteModal = false;
    public bool $hardDeleteConfirm1 = false;
    public bool $hardDeleteConfirm2 = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'filterPriority' => ['except' => 'all'],
        'filterCategory' => ['except' => 'all'],
        'filterAssignment' => ['except' => 'all'],
        'perPage' => ['except' => 15],
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

    public function updatingPerPage()
    {
        $this->resetPage();
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

    public function openHardDeleteModal(): void
    {
        $this->hardDeleteConfirm1 = false;
        $this->hardDeleteConfirm2 = false;
        $this->showHardDeleteModal = true;
    }

    public function closeHardDeleteModal(): void
    {
        $this->showHardDeleteModal = false;
        $this->hardDeleteConfirm1 = false;
        $this->hardDeleteConfirm2 = false;
    }

    public function hardDeleteAll(): void
    {
        // Garde-fou côté serveur : refuser si les 2 cases ne sont pas cochées.
        if (! $this->hardDeleteConfirm1 || ! $this->hardDeleteConfirm2) {
            return;
        }

        // Cascade : verification_assignments et verification_reviews ont onDelete cascade
        // sur page_id, donc deleteAll() suffit pour tout effacer proprement.
        $deleted = VerificationPage::query()->delete();

        $this->closeHardDeleteModal();
        $this->scanResult = null;
        $this->scanError = null;

        session()->flash('success', "Suppression définitive : {$deleted} page(s), avec leurs assignations et relectures.");
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
        $this->releasingQueuedCount = \DB::table('verification_assignments')
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

    public function releaseNow(\App\Services\WeeklyReleaseService $service): void
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
        $existingPairs = \DB::table('verification_assignments')
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
            \DB::table('verification_assignments')->insert($rows);
            $insertCount = count($rows);
        }

        $pageCount = count($this->selectedPageIds);
        $userCount = count($this->bulkAssigneeIds);

        if ($insertCount === 0) {
            session()->flash('success', "Aucune nouvelle assignation : tous les relecteurs choisis étaient déjà assignés à toutes les pages sélectionnées.");
        } else {
            session()->flash('success', "{$insertCount} nouvelle(s) assignation(s) créée(s) sur {$pageCount} page(s) pour {$userCount} relecteur(s). Les pages seront libérées dimanche prochain (ou via « Libérer »).");
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
     * des relecteurs déjà associés. Les nouveaux ont released_at = NULL
     * (en attente du prochain dimanche ou d'un release manuel).
     */
    private function syncAssigneesPreservingReleased(VerificationPage $page, array $userIds): void
    {
        $existing = \DB::table('verification_assignments')
            ->where('page_id', $page->id)
            ->pluck('released_at', 'user_id'); // [user_id => released_at]

        $page->assignees()->sync([]);  // detach all
        foreach ($userIds as $userId) {
            \DB::table('verification_assignments')->insert([
                'page_id' => $page->id,
                'user_id' => $userId,
                'released_at' => $existing[$userId] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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

    public function render()
    {
        $query = VerificationPage::query()
            ->with('assignees')
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

        $pages = $query
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'needs_fix', 'awaiting_validation', 'validated')")
            ->orderByDesc('created_at')
            ->paginate($this->perPage > 0 ? $this->perPage : max(1, VerificationPage::count()));

        $availableUsers = User::where('approved', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $stats = [
            'total' => VerificationPage::count(),
            'pending' => VerificationPage::where('status', 'pending')->count(),
            'needs_fix' => VerificationPage::where('status', 'needs_fix')->count(),
            'awaiting_validation' => VerificationPage::where('status', 'awaiting_validation')->count(),
            'validated' => VerificationPage::where('status', 'validated')->count(),
            'without_assignee' => VerificationPage::withoutAssignee()->count(),
            'in_sitemap' => VerificationPage::where('is_in_sitemap', true)->count(),
            'orphan' => VerificationPage::where('is_in_sitemap', false)
                ->whereNotNull('last_seen_in_sitemap_at')
                ->count(),
        ];

        return view('livewire.verification.admin.pages-manager', [
            'pages' => $pages,
            'availableUsers' => $availableUsers,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
