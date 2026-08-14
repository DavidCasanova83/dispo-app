<?php

namespace App\Livewire\Verification\Admin;

use App\Livewire\Concerns\WithSorting;
use App\Models\User;
use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\VerificationReviewService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewsInbox extends Component
{
    use WithPagination;
    use WithSorting;

    /**
     * Filtre sur le statut de la PAGE (VerificationPage::STATUSES), pas sur celui
     * des relectures : chaque ligne de la liste étant une page, les compteurs des
     * tuiles correspondent ainsi exactement au nombre de lignes affichées.
     */
    public string $filterStatus = 'all';
    // Non typé volontairement : la valeur est hydratée depuis le queryString, où
    // n'importe quoi peut arriver. Un typage ?int ferait planter l'hydratation
    // sur une valeur non numérique. La normalisation se fait dans reviewerId().
    public $filterReviewer = null;
    public string $search = '';

    // Détail (niveau 2)
    public ?int $selectedPageId = null;
    public ?int $activeReviewerUserId = null;

    // Panneau de traitement, ouvert en ligne dans la relecture concernée : l'admin
    // garde le problème signalé sous les yeux pendant qu'il rédige sa réponse.
    public ?int $reviewId = null;
    public string $adminResponse = '';
    public string $newStatus = 'in_progress';

    // Modale de clôture annuelle (purge des reviews + passage en 'validated')
    public bool $showCloseAnnualModal = false;

    protected VerificationReviewService $service;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'filterReviewer' => ['as' => 'relecteur', 'except' => null],
        'search' => ['except' => ''],
        'selectedPageId' => ['as' => 'page', 'except' => null],
        'activeReviewerUserId' => ['as' => 'reviewer', 'except' => null],
    ];

    public function boot(VerificationReviewService $service): void
    {
        $this->service = $service;
    }

    // ─── TRI ──────────────────────────────────────────────────────

    /**
     * @return array<string, string|callable>
     */
    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'pending' => fn (Builder $q, string $dir) => $q->orderBy('pending_admin_count', $dir),
            'oldest' => fn (Builder $q, string $dir) => $q->orderByRaw("oldest_review_at IS NULL, oldest_review_at {$dir}"),
            'activity' => 'updated_at',
            'next_check' => fn (Builder $q, string $dir) => $q->orderByRaw("validated_at IS NULL, validated_at {$dir}"),
        ];
    }

    protected function descendingFirstFields(): array
    {
        return ['pending', 'activity'];
    }

    protected function applyDefaultSorting(Builder $query): Builder
    {
        // Ce qui attend une action de l'admin remonte en premier.
        return $query
            ->orderByDesc('pending_admin_count')
            ->orderByDesc('in_progress_count')
            ->orderByDesc('revision_requested_count')
            ->latest('updated_at');
    }

    // ─── FILTRES ──────────────────────────────────────────────────

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterReviewer(): void
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function applyStatusFilter(string $status): void
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterStatus = 'all';
        $this->filterReviewer = null;
        $this->search = '';
        $this->clearSorting();
    }

    public function hasActiveFilters(): bool
    {
        return $this->filterStatus !== 'all'
            || $this->reviewerId() !== null
            || $this->search !== ''
            || $this->sortField !== '';
    }

    // ─── NAVIGATION LISTE / DÉTAIL ────────────────────────────────

    public function openPageDetail(int $pageId): void
    {
        $this->selectedPageId = $pageId;
        $this->activeReviewerUserId = null;
    }

    public function closePageDetail(): void
    {
        $this->selectedPageId = null;
        $this->activeReviewerUserId = null;
    }

    public function selectReviewer(int $userId): void
    {
        $this->activeReviewerUserId = $userId;
    }

    // ─── MODALE DE TRAITEMENT ─────────────────────────────────────

    public function openResolvePanel(int $reviewId): void
    {
        $review = VerificationReview::findOrFail($reviewId);
        $this->reviewId = $review->id;
        $this->adminResponse = $review->admin_response ?? '';
        $this->newStatus = $review->status === 'pending_admin' ? 'in_progress' : $review->status;
        $this->resetErrorBag();
    }

    public function closeResolvePanel(): void
    {
        $this->reviewId = null;
        $this->adminResponse = '';
        $this->newStatus = 'in_progress';
        $this->resetErrorBag();
    }

    public function resolve(): void
    {
        $this->validate([
            'newStatus' => 'required|in:in_progress,done,revision_requested',
            'adminResponse' => 'nullable|string|max:5000',
        ], [
            'newStatus.required' => 'Vous devez choisir un statut.',
            'newStatus.in' => 'Statut invalide.',
            'adminResponse.max' => 'La réponse ne doit pas dépasser 5000 caractères.',
        ]);

        $review = VerificationReview::findOrFail($this->reviewId);
        $this->service->resolve($review, $this->newStatus, $this->adminResponse);

        session()->flash('success', 'Relecture traitée.');
        $this->closeResolvePanel();
    }

    /**
     * Passe en 'done' toutes les relectures encore ouvertes d'un relecteur sur la
     * page courante. Évite d'ouvrir le panneau une fois par langue quand il n'y a
     * rien à redire. Les relectures déjà traitées ne sont pas retouchées.
     */
    public function validateAllForReviewer(int $userId): void
    {
        if (! $this->selectedPageId) {
            return;
        }

        $reviews = VerificationReview::where('page_id', $this->selectedPageId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending_admin', 'in_progress'])
            ->get();

        if ($reviews->isEmpty()) {
            session()->flash('success', 'Aucune relecture à valider pour ce relecteur : tout est déjà traité.');
            return;
        }

        foreach ($reviews as $review) {
            // On passe par le service : verrou sur la page + recalcul du statut.
            $this->service->resolve($review, 'done', null);
        }

        $count = $reviews->count();
        session()->flash('success', "{$count} relecture(s) validée(s).");
        $this->closeResolvePanel();
    }

    // ─── CLÔTURE ANNUELLE ─────────────────────────────────────────

    public function openCloseAnnualModal(): void
    {
        $this->showCloseAnnualModal = true;
    }

    public function closeCloseAnnualModal(): void
    {
        $this->showCloseAnnualModal = false;
    }

    public function confirmCloseAnnual(): void
    {
        if (! $this->selectedPageId) {
            return;
        }

        $page = VerificationPage::findOrFail($this->selectedPageId);

        // Garde-fou : on n'autorise la clôture qu'à partir de 'awaiting_validation'.
        if ($page->status !== 'awaiting_validation') {
            session()->flash('success', "La page n'est pas prête à être clôturée (statut actuel : {$page->status}).");
            $this->closeCloseAnnualModal();
            return;
        }

        $this->service->closeAnnualVerification($page);

        session()->flash('success', "Vérification annuelle de \"{$page->title}\" clôturée. Les relectures ont été archivées.");
        $this->closeCloseAnnualModal();
        $this->closePageDetail();
    }

    // ─── RENDER ───────────────────────────────────────────────────

    /**
     * Périmètre de la Boîte de retours : les pages ayant au moins une relecture,
     * PLUS les pages déjà clôturées. Ces dernières n'ont plus aucune relecture
     * (la clôture annuelle les purge) mais doivent rester visibles pour suivre
     * l'échéance de la prochaine vérification.
     */
    private function inboxScope(): Builder
    {
        return VerificationPage::query()
            ->where(fn ($q) => $q->whereHas('reviews')->orWhere('status', 'validated'));
    }

    /**
     * Nombre de PAGES par statut dans ce périmètre, en une requête groupée.
     * Chaque compteur est donc le nombre exact de lignes que le filtre affichera.
     */
    private function stats(): array
    {
        $counts = $this->inboxScope()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = ['all' => (int) $counts->sum()];

        foreach (array_keys(VerificationPage::STATUSES) as $code) {
            $stats[$code] = (int) ($counts[$code] ?? 0);
        }

        return $stats;
    }

    public function render()
    {
        $stats = $this->stats();

        $reviewers = User::where('approved', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($this->selectedPageId) {
            return $this->renderDetail($stats, $reviewers);
        }

        return $this->renderList($stats, $reviewers);
    }

    private function renderList(array $stats, $reviewers)
    {
        $query = $this->inboxScope()
            ->with('assignees:id,name')
            ->withCount([
                'reviews as pending_admin_count' => fn ($q) => $q->where('status', 'pending_admin'),
                'reviews as in_progress_count' => fn ($q) => $q->where('status', 'in_progress'),
                'reviews as revision_requested_count' => fn ($q) => $q->where('status', 'revision_requested'),
                'reviews as done_count' => fn ($q) => $q->where('status', 'done'),
                'reviews as total_count',
            ])
            ->withMin('reviews as oldest_review_at', 'created_at')
            ->withAvg('reviews as avg_rating', 'rating');

        // Le filtre porte sur le statut de la page ; on ignore une valeur d'URL
        // qui ne serait pas un statut connu plutôt que de l'injecter en SQL.
        if ($this->filterStatus !== 'all' && array_key_exists($this->filterStatus, VerificationPage::STATUSES)) {
            $query->where('status', $this->filterStatus);
        }

        if ($reviewerId = $this->reviewerId()) {
            // Une page clôturée n'a plus de relecture : on retombe alors sur
            // l'assignation, qui est conservée d'un cycle à l'autre.
            $query->where(fn ($q) => $q
                ->whereHas('reviews', fn ($r) => $r->where('user_id', $reviewerId))
                ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $reviewerId)));
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%');
            });
        }

        $pages = $this->applySorting($query)->paginate(15);

        return view('livewire.verification.admin.reviews-inbox', [
            'view' => 'list',
            'pages' => $pages,
            'stats' => $stats,
            'reviewers' => $reviewers,
        ])->layout('components.layouts.app');
    }

    private function renderDetail(array $stats, $reviewers)
    {
        $page = VerificationPage::with(['reviews.user', 'assignees:id,name'])->find($this->selectedPageId);

        if (! $page) {
            // Page supprimée entretemps : retour liste.
            $this->closePageDetail();
            return $this->renderList($stats, $reviewers);
        }

        // Reviews groupées par relecteur (user_id => collection de reviews FR/EN/IT).
        $reviewsByReviewer = $page->reviews
            ->sortBy([['language', 'asc'], ['updated_at', 'desc']])
            ->groupBy('user_id');

        // Si aucun onglet sélectionné, on prend le premier relecteur disponible.
        if ($this->activeReviewerUserId === null && $reviewsByReviewer->isNotEmpty()) {
            $this->activeReviewerUserId = (int) $reviewsByReviewer->keys()->first();
        }

        $activeReviews = $this->activeReviewerUserId
            ? ($reviewsByReviewer->get($this->activeReviewerUserId) ?? collect())
            : collect();

        return view('livewire.verification.admin.reviews-inbox', [
            'view' => 'detail',
            'page' => $page,
            'reviewsByReviewer' => $reviewsByReviewer,
            'activeReviews' => $activeReviews,
            'stats' => $stats,
            'reviewers' => $reviewers,
        ])->layout('components.layouts.app');
    }
}
