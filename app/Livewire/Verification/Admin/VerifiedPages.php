<?php

namespace App\Livewire\Verification\Admin;

use App\Models\VerificationPage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Onglet « Vérifiées » : pages clôturées, de la prochaine à repartir en
 * relecture à la plus lointaine.
 */
class VerifiedPages extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterCategory = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['as' => 'categorie', 'except' => 'all'],
    ];

    public function updating($property): void
    {
        if (in_array($property, ['search', 'filterCategory'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = VerificationPage::where('status', 'validated')
            ->with('assignees:id,name')
            ->withCount('archivedReviews')
            ->orderByRaw('validated_at IS NULL, validated_at')
            ->orderBy('title');

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('url', 'like', '%'.$this->search.'%'));
        }

        if (isset(VerificationPage::CATEGORIES[$this->filterCategory])) {
            $query->byCategory($this->filterCategory);
        }

        $upcoming = VerificationPage::where('status', 'validated')
            ->whereNotNull('validated_at')
            ->where('validated_at', '<', now()->subDays(VerificationPage::REVALIDATION_DAYS - 30))
            ->count();

        return view('livewire.verification.admin.verified-pages', [
            'pages' => $query->paginate(25),
            'upcoming' => $upcoming,
        ])->layout('components.layouts.app');
    }
}
