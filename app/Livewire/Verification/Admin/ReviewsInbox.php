<?php

namespace App\Livewire\Verification\Admin;

use App\Models\VerificationPage;
use App\Models\VerificationReview;
use App\Services\VerificationReviewService;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewsInbox extends Component
{
    use WithPagination;

    // Liste (niveau 1)
    public string $filterStatus = 'all';
    public string $search = '';

    // Détail (niveau 2)
    public ?int $selectedPageId = null;
    public ?int $activeReviewerUserId = null;

    // Modale de traitement d'une review
    public bool $showResolveModal = false;
    public ?int $reviewId = null;
    public string $adminResponse = '';
    public string $newStatus = 'in_progress';

    // Modale de clôture annuelle (purge des reviews + passage en 'validated')
    public bool $showCloseAnnualModal = false;

    protected VerificationReviewService $service;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'search' => ['except' => ''],
        'selectedPageId' => ['as' => 'page', 'except' => null],
        'activeReviewerUserId' => ['as' => 'reviewer', 'except' => null],
    ];

    public function boot(VerificationReviewService $service): void
    {
        $this->service = $service;
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
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

    public function openResolveModal(int $reviewId): void
    {
        $review = VerificationReview::findOrFail($reviewId);
        $this->reviewId = $review->id;
        $this->adminResponse = $review->admin_response ?? '';
        $this->newStatus = $review->status === 'pending_admin' ? 'in_progress' : $review->status;
        $this->resetErrorBag();
        $this->showResolveModal = true;
    }

    public function closeResolveModal(): void
    {
        $this->showResolveModal = false;
        $this->reviewId = null;
        $this->adminResponse = '';
        $this->newStatus = 'in_progress';
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
        $this->service->resolve($review, $this->newStatus, $this->adminResponse ?: null);

        session()->flash('success', 'Relecture traitée.');
        $this->closeResolveModal();
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

    public function render()
    {
        $stats = [
            'pending_admin' => VerificationReview::where('status', 'pending_admin')->count(),
            'in_progress' => VerificationReview::where('status', 'in_progress')->count(),
            'revision_requested' => VerificationReview::where('status', 'revision_requested')->count(),
            'done' => VerificationReview::where('status', 'done')->count(),
        ];

        if ($this->selectedPageId) {
            return $this->renderDetail($stats);
        }

        return $this->renderList($stats);
    }

    private function renderList(array $stats)
    {
        // Pages ayant au moins une review, filtrées + paginées.
        $pageQuery = VerificationPage::query()
            ->whereHas('reviews', function ($q) {
                if ($this->filterStatus !== 'all') {
                    $q->where('status', $this->filterStatus);
                }
            })
            ->with(['reviews.user']);

        if ($this->search !== '') {
            $pageQuery->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%');
            });
        }

        // Tri : pages avec reviews pending_admin en premier, puis in_progress, puis tout traité.
        // On calcule un poids par page selon la review la plus "urgente".
        $pageQuery->withCount([
            'reviews as pending_admin_count' => fn ($q) => $q->where('status', 'pending_admin'),
            'reviews as in_progress_count' => fn ($q) => $q->where('status', 'in_progress'),
            'reviews as revision_requested_count' => fn ($q) => $q->where('status', 'revision_requested'),
            'reviews as done_count' => fn ($q) => $q->where('status', 'done'),
            'reviews as total_count',
        ]);

        $pages = $pageQuery
            ->orderByDesc('pending_admin_count')
            ->orderByDesc('in_progress_count')
            ->orderByDesc('revision_requested_count')
            ->latest('updated_at')
            ->paginate(15);

        return view('livewire.verification.admin.reviews-inbox', [
            'view' => 'list',
            'pages' => $pages,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }

    private function renderDetail(array $stats)
    {
        $page = VerificationPage::with(['reviews.user'])->find($this->selectedPageId);

        if (! $page) {
            // Page supprimée entretemps : retour liste.
            $this->closePageDetail();
            return $this->renderList($stats);
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
        ])->layout('components.layouts.app');
    }
}
