<?php

namespace App\Livewire\Verification\Admin;

use App\Models\VerificationPage;
use App\Services\VerificationPageService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Onglet « Corbeille » : pages retirées du suivi, avec leurs relectures, leurs
 * assignations et leur journal, restaurables en un clic.
 */
class Trash extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function restore(int $pageId): void
    {
        $page = app(VerificationPageService::class)->restore($pageId);

        session()->flash('success', "« {$page->title} » est restaurée, avec ses relecteurs et ses relectures.");
    }

    public function render()
    {
        $query = VerificationPage::onlyTrashed()
            ->withCount(['allReviews', 'assignees'])
            ->orderByDesc('deleted_at');

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('url', 'like', '%'.$this->search.'%'));
        }

        return view('livewire.verification.admin.trash', [
            'pages' => $query->paginate(25),
        ])->layout('components.layouts.app');
    }
}
