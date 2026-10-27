<?php

namespace App\Livewire\Verification\Admin;

use App\Livewire\Concerns\ResolvesReviews;
use App\Models\VerificationPage;
use App\Services\VerificationOverviewService;
use App\Services\VerificationReviewService;
use Livewire\Component;

/**
 * Onglet « À traiter » : uniquement ce qui attend l'admin. Les nouveaux
 * retours (du plus ancien au plus récent), les corrections en cours, et les
 * pages prêtes à clôturer, clôturables en masse.
 */
class ToHandle extends Component
{
    use ResolvesReviews;

    /** Nombre de retours affichés avant « Tout afficher ». */
    public const PREVIEW = 10;

    public bool $showAllNew = false;

    public bool $showAllInProgress = false;

    /** @var int[] Pages prêtes cochées pour la clôture en masse */
    public array $selectedReady = [];

    public bool $showCloseModal = false;

    /** @var int[] Pages à clôturer après confirmation */
    public array $closingIds = [];

    public function selectAllReady(): void
    {
        $this->selectedReady = VerificationPage::where('status', 'awaiting_validation')->pluck('id')->all();
    }

    public function clearReadySelection(): void
    {
        $this->selectedReady = [];
    }

    /**
     * @param  int|null  $pageId  une page seule, sinon la sélection
     */
    public function askClose(?int $pageId = null): void
    {
        $this->closingIds = $pageId ? [$pageId] : array_map('intval', $this->selectedReady);

        if ($this->closingIds !== []) {
            $this->showCloseModal = true;
        }
    }

    public function cancelClose(): void
    {
        $this->showCloseModal = false;
        $this->closingIds = [];
    }

    public function confirmClose(VerificationReviewService $service): void
    {
        $result = $service->closeMany($this->closingIds);

        $message = $result['closed'] > 1
            ? "{$result['closed']} pages clôturées : vérifiées pour un an. {$result['archived']} relecture(s) archivée(s), consultables dans leur fiche."
            : ($result['closed'] === 1
                ? "Page clôturée : vérifiée pour un an. {$result['archived']} relecture(s) archivée(s), consultables dans sa fiche."
                : 'Aucune page clôturée.');
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} page(s) ignorée(s) : elles n'étaient plus prêtes à clôturer.";
        }

        session()->flash('success', $message);
        $this->selectedReady = array_values(array_diff($this->selectedReady, $this->closingIds));
        $this->cancelClose();
    }

    public function render()
    {
        $overview = app(VerificationOverviewService::class);

        $newQuery = $overview->activeReviewsWithStatus('pending_admin')
            ->with(['page', 'user:id,name'])
            ->orderByRaw('COALESCE(submitted_at, created_at)');
        $newCount = (clone $newQuery)->count();

        $progressQuery = $overview->activeReviewsWithStatus('in_progress')
            ->with(['page', 'user:id,name'])
            ->orderBy('updated_at');
        $progressCount = (clone $progressQuery)->count();

        $readyPages = VerificationPage::where('status', 'awaiting_validation')
            ->with(['assignees:id,name', 'reviews.user:id,name'])
            ->orderBy('updated_at')
            ->get();

        $closing = $this->showCloseModal
            ? VerificationPage::whereIn('id', $this->closingIds)->withCount('reviews')->get()
            : collect();

        return view('livewire.verification.admin.to-handle', [
            'newReviews' => $this->showAllNew ? $newQuery->get() : $newQuery->limit(self::PREVIEW)->get(),
            'newCount' => $newCount,
            'progressReviews' => $this->showAllInProgress ? $progressQuery->get() : $progressQuery->limit(self::PREVIEW)->get(),
            'progressCount' => $progressCount,
            'readyPages' => $readyPages,
            'closing' => $closing,
        ])->layout('components.layouts.app');
    }
}
