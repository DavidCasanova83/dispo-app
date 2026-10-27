<?php

namespace App\Livewire\Verification\Admin;

use App\Livewire\Concerns\EditsVerificationPage;
use App\Livewire\Concerns\ResolvesReviews;
use App\Models\VerificationPage;
use App\Services\PageReleaseService;
use App\Services\VerificationPageService;
use App\Services\VerificationReviewService;
use Livewire\Component;

/**
 * Fiche d'une page : étape, retours de chaque relecteur du cycle en cours (y
 * compris ceux qui n'ont pas encore répondu), relecteurs, état des traductions,
 * cycles clôturés consultables et journal.
 */
class PageSheet extends Component
{
    use EditsVerificationPage;
    use ResolvesReviews;

    public int $pageId;

    public ?string $addReviewerId = null;

    public ?int $confirmRemoveReviewer = null;

    public bool $showCloseModal = false;

    public bool $showRemoveModal = false;

    /** Cycle clôturé déplié dans l'historique */
    public ?int $openCycle = null;

    public bool $showFullJournal = false;

    public function mount(int $page): void
    {
        $this->pageId = VerificationPage::withTrashed()->findOrFail($page)->id;
    }

    private function page(): VerificationPage
    {
        return VerificationPage::withTrashed()->findOrFail($this->pageId);
    }

    // ─── RELECTEURS ───────────────────────────────────────────────

    public function addReviewer(VerificationPageService $service): void
    {
        if (! is_numeric($this->addReviewerId)) {
            return;
        }

        $created = $service->addAssignees([$this->pageId], [(int) $this->addReviewerId]);
        $this->addReviewerId = null;

        session()->flash('success', $created > 0
            ? 'Relecteur ajouté. Il recevra la page au fil des places libres (distribution chaque nuit), ou tout de suite avec « Distribuer maintenant ».'
            : 'Ce relecteur est déjà assigné à la page.');
    }

    public function removeReviewer(int $userId): void
    {
        app(VerificationPageService::class)->removeAssignees([$this->pageId], [$userId]);
        $this->confirmRemoveReviewer = null;

        session()->flash('success', 'Relecteur retiré. Ses relectures sont conservées et son assignation est notée au journal.');
    }

    public function releaseNow(?int $userId = null): void
    {
        $count = app(PageReleaseService::class)->releasePageNow($this->pageId, $userId);

        session()->flash('success', $count === 0
            ? 'Rien à distribuer : les relecteurs ont déjà la page.'
            : "Page distribuée à {$count} relecteur(s) : elle est sur leur tableau de bord dès maintenant.");
    }

    // ─── CLÔTURE ──────────────────────────────────────────────────

    public function askClose(): void
    {
        $this->showCloseModal = true;
    }

    public function cancelClose(): void
    {
        $this->showCloseModal = false;
    }

    public function confirmClose(VerificationReviewService $service): void
    {
        $result = $service->closeMany([$this->pageId]);
        $this->showCloseModal = false;

        session()->flash('success', $result['closed'] === 1
            ? "Page clôturée : vérifiée pour un an. {$result['archived']} relecture(s) archivée(s), consultables ci-dessous dans l'historique."
            : 'La page n\'est plus prête à clôturer : un retour a changé entre-temps.');
    }

    // ─── RETRAIT DU SUIVI ─────────────────────────────────────────

    public function confirmRemovePage(VerificationPageService $service): void
    {
        $service->remove([$this->pageId]);
        $this->showRemoveModal = false;
        session()->flash('success', 'Page retirée du suivi et placée dans la corbeille, avec ses relectures et son journal.');
    }

    public function restorePage(VerificationPageService $service): void
    {
        $service->restore($this->pageId);
        session()->flash('success', 'Page restaurée : elle reprend sa place dans le suivi, avec ses relecteurs et ses relectures.');
    }

    // ─── RENDU ────────────────────────────────────────────────────

    public function render()
    {
        $page = VerificationPage::withTrashed()
            ->with([
                'assignees:id,name',
                'reviews.user:id,name',
                'translationChecks',
            ])
            ->findOrFail($this->pageId);

        $progress = $page->reviewerProgress();

        // Anciennes relectures EN/IT du cycle en cours (avant le module Traductions).
        $translationReviews = $page->reviews->where('language', '!=', 'fr')->sortBy('language')->values();

        $archived = $page->archivedReviews()->with('user:id,name')->orderBy('language')->get()->groupBy('cycle')->sortKeysDesc();
        $closures = $page->events()->where('type', 'closed')->with('actor:id,name')->get()->keyBy(fn ($e) => $e->data['cycle'] ?? $e->cycle);

        $events = $page->events()->with(['actor:id,name', 'user:id,name'])->limit($this->showFullJournal ? 500 : 12)->get();

        $assignedIds = $page->assignees->pluck('id');

        return view('livewire.verification.admin.page-sheet', [
            'page' => $page,
            'progress' => $progress,
            'translationReviews' => $translationReviews,
            'archived' => $archived,
            'closures' => $closures,
            'events' => $events,
            'eventsTotal' => $page->events()->count(),
            'availableUsers' => app(VerificationPageService::class)->availableReviewers(),
            'addableUsers' => app(VerificationPageService::class)->availableReviewers()->whereNotIn('id', $assignedIds),
            'closingCount' => $page->reviews->count(),
        ])->layout('components.layouts.app');
    }
}
