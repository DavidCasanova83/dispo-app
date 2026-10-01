<?php

namespace App\Livewire\Verification\Translation;

use App\Models\VerificationPage;
use App\Services\TranslationCheckService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Espace traducteur : toutes les pages du site avec l'état de leurs traductions EN/IT.
 */
class TranslationList extends Component
{
    use WithPagination;

    public string $filterStatus = 'to_check';

    public string $filterLanguage = 'all';

    public string $filterCategory = '';

    public string $search = '';

    protected $queryString = [
        'filterStatus' => ['as' => 'etat', 'except' => 'to_check'],
        'filterLanguage' => ['as' => 'langue', 'except' => 'all'],
        'filterCategory' => ['as' => 'categorie', 'except' => ''],
        'search' => ['except' => ''],
    ];

    protected TranslationCheckService $service;

    public function boot(TranslationCheckService $service): void
    {
        $this->service = $service;
    }

    public function updating($property): void
    {
        if (in_array($property, ['filterLanguage', 'filterCategory', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function applyStatusFilter(string $status): void
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterStatus = 'all';
        $this->filterLanguage = 'all';
        $this->filterCategory = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * @return string[]
     */
    private function languages(): array
    {
        return $this->service->normalizeLanguages($this->filterLanguage === 'all' ? [] : [$this->filterLanguage]);
    }

    private function baseQuery()
    {
        $query = VerificationPage::forTranslation();

        if ($this->filterCategory !== '' && isset(VerificationPage::CATEGORIES[$this->filterCategory])) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('url', 'like', '%'.$this->search.'%'));
        }

        return $query;
    }

    public function render()
    {
        $languages = $this->languages();
        $stats = $this->service->stats($this->baseQuery(), $languages);

        $query = $this->baseQuery()->with('translationChecks')->orderBy('title');
        $this->service->applyFilter($query, $this->filterStatus, $languages);

        return view('livewire.verification.translation.translation-list', [
            'pages' => $query->paginate(20),
            'stats' => $stats,
            'languages' => $languages,
        ])->layout('components.layouts.app');
    }
}
