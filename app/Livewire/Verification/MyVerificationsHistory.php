<?php

namespace App\Livewire\Verification;

use App\Services\VerificationReviewService;
use Livewire\Component;
use Livewire\WithPagination;

class MyVerificationsHistory extends Component
{
    use WithPagination;

    public string $filterStatus = 'all';

    public string $filterPeriod = 'all';

    /** @var int[] Relectures traitées par l'admin depuis la dernière visite (pastille « Nouveau ») */
    public array $newIds = [];

    protected VerificationReviewService $service;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'filterPeriod' => ['except' => 'all'],
    ];

    public function boot(VerificationReviewService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        // On retient ce qui est nouveau pour l'afficher, puis on le marque comme vu
        // (la pastille du menu disparaît).
        $this->newIds = \App\Models\VerificationReview::where('user_id', auth()->id())
            ->unseenByReviewer()
            ->pluck('id')
            ->all();

        $this->service->markResponsesSeen(auth()->user());
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterPeriod()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $reviews = $this->service
            ->historyForUser($user, [
                'status' => $this->filterStatus,
                'period' => $this->filterPeriod,
            ])
            ->paginate(10);

        $totalDone = $this->service->userVerificationCount($user);

        return view('livewire.verification.my-verifications-history', [
            'reviews' => $reviews,
            'totalDone' => $totalDone,
        ])->layout('components.layouts.app');
    }
}
