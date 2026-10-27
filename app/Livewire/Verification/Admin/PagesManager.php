<?php

namespace App\Livewire\Verification\Admin;

use App\Livewire\Concerns\EditsVerificationPage;
use App\Livewire\Concerns\WithSorting;
use App\Models\VerificationPage;
use App\Services\PageReleaseService;
use App\Services\SitemapScanService;
use App\Services\VerificationPageService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Onglet « Pages » : toutes les pages suivies, leur étape et l'avancement de
 * chaque relecteur ; ajout, relecteurs, actions en masse, scan du site et
 * réglage du plafond de pages ouvertes.
 */
class PagesManager extends Component
{
    use EditsVerificationPage;
    use WithPagination;
    use WithSorting;

    public string $search = '';

    public string $filterStage = '';           // une clé de VerificationPage::STAGES

    public string $filterPriority = 'all';

    public string $filterCategory = 'all';

    // Non typé volontairement : la valeur est hydratée depuis le queryString, où
    // n'importe quoi peut arriver. La normalisation se fait dans reviewerId().
    public $filterReviewer = null;

    public bool $filterOverdue = false;

    public bool $filterOutOfSite = false;

    public int $perPage = 25;

    // Relecteurs d'une page
    public bool $showAssignModal = false;

    public ?int $assigningPageId = null;

    public array $quickAssigneeIds = [];

    // Sélection et actions en masse
    public array $selectedPageIds = [];

    /** assign | unassign | priority | deadline | category | release | remove */
    public string $bulkAction = '';

    public array $bulkUserIds = [];

    public string $bulkPriority = 'medium';

    public ?string $bulkDeadline = null;

    public string $bulkCategory = '';

    // Retrait du suivi (corbeille)
    public bool $showRemoveModal = false;

    public array $removingIds = [];

    public bool $removingOutOfSite = false;

    // Scan du site
    public ?array $scanResult = null;

    public ?string $scanError = null;

    public bool $showResetModal = false;

    // Réglages
    public bool $showSettingsModal = false;

    public int $quota = PageReleaseService::ACTIVE_PAGES_QUOTA;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStage' => ['as' => 'etape', 'except' => ''],
        'filterPriority' => ['as' => 'priorite', 'except' => 'all'],
        'filterCategory' => ['as' => 'categorie', 'except' => 'all'],
        'filterReviewer' => ['as' => 'relecteur', 'except' => null],
        'filterOverdue' => ['as' => 'retard', 'except' => false],
        'filterOutOfSite' => ['as' => 'hors_site', 'except' => false],
        'perPage' => ['except' => 25],
    ];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const BULK_ACTIONS = [
        'assign' => 'Ajouter des relecteurs',
        'unassign' => 'Retirer des relecteurs',
        'priority' => 'Changer la priorité',
        'deadline' => 'Fixer l\'échéance',
        'category' => 'Changer la catégorie',
        'release' => 'Distribuer maintenant',
        'remove' => 'Retirer du suivi',
    ];

    public function mount(): void
    {
        if (request()->boolean('ajouter')) {
            $this->openCreateModal();
        }

        if ($this->filterStage !== '' && ! isset(VerificationPage::STAGES[$this->filterStage])) {
            $this->filterStage = '';
        }
    }

    // ─── TRI ──────────────────────────────────────────────────────

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'stage' => fn (Builder $q, string $dir) => $q->orderByRaw(VerificationPage::orderBySql('('.VerificationPage::stageSql().')', array_keys(VerificationPage::STAGES))." {$dir}"),
            'deadline' => fn (Builder $q, string $dir) => $q->orderByRaw("deadline IS NULL, deadline {$dir}"),
            'priority' => fn (Builder $q, string $dir) => $q->orderByRaw(VerificationPage::priorityOrderSql()." {$dir}"),
            'created_at' => 'created_at',
        ];
    }

    protected function descendingFirstFields(): array
    {
        return ['created_at'];
    }

    protected function applyDefaultSorting(Builder $query): Builder
    {
        // Ce qui demande une action d'abord, dans l'ordre du circuit.
        return $query
            ->orderByRaw(VerificationPage::orderBySql('('.VerificationPage::stageSql().')', array_keys(VerificationPage::STAGES)))
            ->orderBy('title');
    }

    // ─── FILTRES ──────────────────────────────────────────────────

    public function updating($property): void
    {
        if (in_array($property, ['search', 'filterStage', 'filterPriority', 'filterCategory', 'filterReviewer', 'filterOverdue', 'filterOutOfSite'], true)) {
            $this->resetPage();
        }
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 25;
        }
        $this->resetPage();
    }

    private function reviewerId(): ?int
    {
        return is_numeric($this->filterReviewer) ? (int) $this->filterReviewer : null;
    }

    /** Raccourcis : un clic remplace les autres raccourcis plutôt que de s'y ajouter. */
    public function applyQuickFilter(string $key): void
    {
        $this->filterStage = $key === 'unassigned' ? 'unassigned' : '';
        $this->filterOverdue = $key === 'overdue';
        $this->filterOutOfSite = $key === 'out_of_site';
        $this->resetPage();
    }

    public function activeQuickFilter(): string
    {
        return match (true) {
            $this->filterOutOfSite && $this->filterStage === '' && ! $this->filterOverdue => 'out_of_site',
            $this->filterOverdue && $this->filterStage === '' && ! $this->filterOutOfSite => 'overdue',
            $this->filterStage === 'unassigned' && ! $this->filterOverdue && ! $this->filterOutOfSite => 'unassigned',
            $this->filterStage === '' && ! $this->filterOverdue && ! $this->filterOutOfSite => 'all',
            default => '',
        };
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStage = '';
        $this->filterPriority = 'all';
        $this->filterCategory = 'all';
        $this->filterReviewer = null;
        $this->filterOverdue = false;
        $this->filterOutOfSite = false;
        $this->clearSorting();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->filterStage !== ''
            || $this->filterPriority !== 'all'
            || $this->filterCategory !== 'all'
            || $this->reviewerId() !== null
            || $this->filterOverdue
            || $this->filterOutOfSite
            || $this->sortField !== '';
    }

    // ─── SCAN DU SITE ─────────────────────────────────────────────

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

    public function resetSitemapTracking(SitemapScanService $service): void
    {
        $count = $service->resetTracking();
        $this->showResetModal = false;
        $this->dismissScanResult();
        session()->flash('success', "Suivi du site réinitialisé pour {$count} page(s). Lancez un nouveau scan pour le rafraîchir.");
    }

    // ─── RÉGLAGES ─────────────────────────────────────────────────

    public function openSettings(): void
    {
        $this->quota = PageReleaseService::quota();
        $this->resetErrorBag('quota');
        $this->showSettingsModal = true;
    }

    public function saveSettings(): void
    {
        $this->validate(
            ['quota' => 'required|integer|min:'.PageReleaseService::QUOTA_MIN.'|max:'.PageReleaseService::QUOTA_MAX],
            [
                'quota.required' => 'Indiquez un nombre de pages.',
                'quota.integer' => 'Indiquez un nombre entier.',
                'quota.min' => 'Au moins '.PageReleaseService::QUOTA_MIN.' page.',
                'quota.max' => 'Au plus '.PageReleaseService::QUOTA_MAX.' pages.',
            ],
        );

        $quota = PageReleaseService::setQuota($this->quota);
        $this->showSettingsModal = false;
        session()->flash('success', "Réglage enregistré : {$quota} page".($quota > 1 ? 's' : '').' ouverte'.($quota > 1 ? 's' : '').' au plus par relecteur. Appliqué dès la prochaine distribution (cette nuit à 3 h 30).');
    }

    // ─── RELECTEURS D'UNE PAGE ────────────────────────────────────

    public function openAssignModal(int $pageId): void
    {
        $page = VerificationPage::with('assignees')->findOrFail($pageId);
        $this->assigningPageId = $page->id;
        $this->quickAssigneeIds = $page->assignees->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->assigningPageId = null;
        $this->quickAssigneeIds = [];
    }

    public function saveAssignment(VerificationPageService $service): void
    {
        if (! $this->assigningPageId) {
            return;
        }

        $service->syncAssignees(VerificationPage::findOrFail($this->assigningPageId), $this->quickAssigneeIds);
        session()->flash('success', 'Relecteurs enregistrés. Les nouveaux reçoivent la page au fil des places libres (distribution chaque nuit).');
        $this->closeAssignModal();
    }

    public function releaseNow(int $pageId): void
    {
        $count = app(PageReleaseService::class)->releasePageNow($pageId);

        session()->flash('success', $count === 0
            ? 'Aucun relecteur en attente sur cette page : elle est déjà distribuée.'
            : "Page distribuée à {$count} relecteur(s) : elle est sur leur tableau de bord dès maintenant, même s'ils ont déjà ".PageReleaseService::quota().' page(s) ouverte(s).');
    }

    // ─── SÉLECTION ET ACTIONS EN MASSE ────────────────────────────

    public function toggleSelectAll(bool $checked, array $idsOnPage): void
    {
        $idsOnPage = array_map('intval', $idsOnPage);
        $current = array_map('intval', $this->selectedPageIds);

        $this->selectedPageIds = $checked
            ? array_values(array_unique(array_merge($current, $idsOnPage)))
            : array_values(array_diff($current, $idsOnPage));
    }

    public function clearSelection(): void
    {
        $this->selectedPageIds = [];
    }

    public function openBulk(string $action): void
    {
        if (empty($this->selectedPageIds) || ! isset(self::BULK_ACTIONS[$action])) {
            return;
        }

        $this->bulkAction = $action;
        $this->bulkUserIds = [];
        $this->bulkPriority = 'medium';
        $this->bulkDeadline = null;
        $this->bulkCategory = '';
        $this->resetErrorBag();
    }

    public function closeBulk(): void
    {
        $this->bulkAction = '';
        $this->resetErrorBag();
    }

    public function applyBulk(VerificationPageService $pages, PageReleaseService $release): void
    {
        $ids = array_values(array_unique(array_map('intval', $this->selectedPageIds)));
        $count = count($ids);

        if ($count === 0) {
            $this->closeBulk();

            return;
        }

        $message = match ($this->bulkAction) {
            'assign' => $this->bulkAssign($pages, $ids),
            'unassign' => $this->bulkUnassign($pages, $ids),
            'priority' => $this->bulkUpdate($ids, ['priority' => in_array($this->bulkPriority, ['low', 'medium', 'high'], true) ? $this->bulkPriority : 'medium'], 'priorité'),
            'deadline' => $this->bulkDeadlineUpdate($ids),
            'category' => $this->bulkUpdate($ids, ['category' => isset(VerificationPage::CATEGORIES[$this->bulkCategory]) ? $this->bulkCategory : null], 'catégorie'),
            'release' => $this->bulkRelease($release, $ids),
            'remove' => null,
            default => null,
        };

        if ($this->bulkAction === 'remove') {
            $this->closeBulk();
            $this->askRemove($ids);

            return;
        }

        if ($message === null) {
            return;   // erreur de validation affichée dans la modale
        }

        session()->flash('success', $message);
        $this->clearSelection();
        $this->closeBulk();
    }

    private function bulkAssign(VerificationPageService $pages, array $ids): ?string
    {
        if (empty($this->bulkUserIds)) {
            $this->addError('bulkUserIds', 'Choisissez au moins un relecteur.');

            return null;
        }

        $created = $pages->addAssignees($ids, $this->bulkUserIds);

        return $created === 0
            ? 'Aucune nouvelle assignation : ces relecteurs étaient déjà assignés à toutes les pages choisies.'
            : "{$created} assignation(s) ajoutée(s) sur ".count($ids).' page(s). Distribution au fil des places libres (chaque nuit), ou tout de suite avec « Distribuer maintenant ».';
    }

    private function bulkUnassign(VerificationPageService $pages, array $ids): ?string
    {
        if (empty($this->bulkUserIds)) {
            $this->addError('bulkUserIds', 'Choisissez au moins un relecteur.');

            return null;
        }

        $removed = $pages->removeAssignees($ids, $this->bulkUserIds);

        return $removed === 0
            ? 'Aucun de ces relecteurs n\'était assigné aux pages choisies.'
            : "{$removed} assignation(s) retirée(s). Les relectures déjà rendues sont conservées.";
    }

    private function bulkUpdate(array $ids, array $values, string $what): string
    {
        $count = VerificationPage::whereIn('id', $ids)->update($values);

        return ucfirst($what)." mise à jour sur {$count} page(s).";
    }

    private function bulkDeadlineUpdate(array $ids): ?string
    {
        $this->validate(['bulkDeadline' => 'nullable|date'], ['bulkDeadline.date' => 'L\'échéance n\'est pas une date valide.']);

        $count = VerificationPage::whereIn('id', $ids)->update(['deadline' => $this->bulkDeadline ?: null]);

        return $this->bulkDeadline
            ? 'Échéance fixée au '.\Illuminate\Support\Carbon::parse($this->bulkDeadline)->format('d/m/Y')." sur {$count} page(s)."
            : "Échéance retirée sur {$count} page(s).";
    }

    private function bulkRelease(PageReleaseService $release, array $ids): string
    {
        $total = 0;
        foreach ($ids as $id) {
            $total += $release->releasePageNow($id);
        }

        return $total === 0
            ? 'Rien à distribuer : les relecteurs de ces pages les ont déjà.'
            : "{$total} distribution(s) faite(s) : les pages sont sur le tableau de bord des relecteurs dès maintenant.";
    }

    // ─── RETRAIT DU SUIVI (CORBEILLE) ─────────────────────────────

    public function askRemove(array $ids): void
    {
        $this->removingIds = array_values(array_unique(array_map('intval', $ids)));
        $this->removingOutOfSite = false;
        $this->showRemoveModal = $this->removingIds !== [];
    }

    public function askRemoveOutOfSite(): void
    {
        $this->removingIds = VerificationPage::outOfSite()->pluck('id')->all();
        $this->removingOutOfSite = true;
        $this->showRemoveModal = $this->removingIds !== [];
    }

    public function cancelRemove(): void
    {
        $this->showRemoveModal = false;
        $this->removingIds = [];
        $this->removingOutOfSite = false;
    }

    public function confirmRemove(VerificationPageService $service): void
    {
        $count = $service->remove($this->removingIds);

        session()->flash('success', "{$count} page(s) retirée(s) du suivi et placée(s) dans la corbeille, avec leurs relectures. Restaurables à tout moment.");
        $this->selectedPageIds = array_values(array_diff(array_map('intval', $this->selectedPageIds), $this->removingIds));
        $this->cancelRemove();
    }

    // ─── RENDU ────────────────────────────────────────────────────

    public function render()
    {
        $query = VerificationPage::query()
            ->with(['assignees:id,name', 'reviews' => fn ($q) => $q->where('language', 'fr')->with('user:id,name')]);

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('url', 'like', '%'.$this->search.'%')
                ->orWhere('theme', 'like', '%'.$this->search.'%'));
        }

        if ($this->filterStage !== '' && isset(VerificationPage::STAGES[$this->filterStage])) {
            $query->inStage($this->filterStage);
        }

        if (in_array($this->filterPriority, ['low', 'medium', 'high'], true)) {
            $query->where('priority', $this->filterPriority);
        }

        if ($this->filterCategory === 'none') {
            $query->whereNull('category');
        } elseif (isset(VerificationPage::CATEGORIES[$this->filterCategory])) {
            $query->byCategory($this->filterCategory);
        }

        if ($reviewerId = $this->reviewerId()) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $reviewerId));
        }

        if ($this->filterOverdue) {
            $query->where('status', '!=', 'validated')
                ->whereNotNull('deadline')
                ->whereDate('deadline', '<', now()->toDateString());
        }

        if ($this->filterOutOfSite) {
            $query->outOfSite();
        }

        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 25;

        $counts = [
            'all' => VerificationPage::count(),
            'unassigned' => VerificationPage::inStage('unassigned')->count(),
            'overdue' => VerificationPage::where('status', '!=', 'validated')->whereNotNull('deadline')->whereDate('deadline', '<', now()->toDateString())->count(),
            'out_of_site' => VerificationPage::outOfSite()->count(),
        ];

        $lastScan = VerificationPage::max('last_seen_in_sitemap_at');

        return view('livewire.verification.admin.pages-manager', [
            'pages' => $this->applySorting($query)->paginate($perPage),
            'availableUsers' => app(VerificationPageService::class)->availableReviewers(),
            'counts' => $counts,
            'lastScan' => $lastScan ? \Illuminate\Support\Carbon::parse($lastScan) : null,
            'removing' => $this->showRemoveModal
                ? VerificationPage::whereIn('id', $this->removingIds)->withCount('allReviews')->get(['id', 'title'])
                : collect(),
        ])->layout('components.layouts.app');
    }
}
