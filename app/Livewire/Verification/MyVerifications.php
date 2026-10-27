<?php

namespace App\Livewire\Verification;

use App\Services\VerificationReviewService;
use Livewire\Component;

class MyVerifications extends Component
{
    protected VerificationReviewService $service;

    public function boot(VerificationReviewService $service): void
    {
        $this->service = $service;
    }

    public function render()
    {
        $user = auth()->user();
        $pages = $this->service->pendingForUser($user);
        $totalDone = $this->service->userVerificationCount($user);

        return view('livewire.verification.my-verifications', [
            'pages' => $pages,
            'totalDone' => $totalDone,
            'unseenResponses' => $this->service->unseenResponsesCount($user),
        ])->layout('components.layouts.app');
    }
}
