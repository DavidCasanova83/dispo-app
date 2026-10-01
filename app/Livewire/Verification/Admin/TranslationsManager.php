<?php

namespace App\Livewire\Verification\Admin;

use App\Models\TranslationCheck;
use App\Models\VerificationPage;
use App\Services\TranslationCheckService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Suivi des traductions (super-admin) : état EN/IT de toutes les pages et
 * traitement des corrections signalées par les traducteurs.
 */
class TranslationsManager extends Component
{
    use WithPagination;

    public string $filterStatus = 'all';

    public string $filterLanguage = 'all';

    public string $filterCategory = '';

    public string $search = '';

    // Détail d'une page (niveau 2)
    public ?int $selectedPageId = null;

    /** @var array<string, string> réponse de l'admin, par langue */
    public array $responses = [];

    protected $queryString = [
        'filterStatus' => ['as' => 'etat', 'except' => 'all'],
        'filterLanguage' => ['as' => 'langue', 'except' => 'all'],
        'filterCategory' => ['as' => 'categorie', 'except' => ''],
        'search' => ['except' => ''],
        'selectedPageId' => ['as' => 'fiche', 'except' => null], // « page » est pris par la pagination
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

    public function hasActiveFilters(): bool
    {
        return $this->filterStatus !== 'all' || $this->filterLanguage !== 'all'
            || $this->filterCategory !== '' || $this->search !== '';
    }

    // ─── DÉTAIL ET TRAITEMENT ─────────────────────────────────────

    public function openPage(int $pageId): void
    {
        $this->selectedPageId = $pageId;
        $this->loadResponses();
    }

    public function closePage(): void
    {
        $this->selectedPageId = null;
        $this->responses = [];
        $this->resetErrorBag();
    }

    public function markInProgress(string $language): void
    {
        $this->resolve($language, 'in_progress', 'Traduction passée en cours de correction.');
    }

    public function markFixed(string $language): void
    {
        $this->resolve($language, 'fixed', 'Traduction marquée corrigée et validée.');
    }

    public function requestRecheck(string $language): void
    {
        $page = $this->selectedPage();
        abort_unless($page && isset(TranslationCheck::LANGUAGES[$language]), 404);

        $this->service->requestRecheck($page, $language);
        $this->responses[$language] = '';

        session()->flash('success', 'Nouvelle vérification '.TranslationCheck::LANGUAGES[$language]['short'].' demandée.');
    }

    private function resolve(string $language, string $status, string $message): void
    {
        $page = $this->selectedPage();
        abort_unless($page && isset(TranslationCheck::LANGUAGES[$language]), 404);

        $this->validate(
            ["responses.{$language}" => ['nullable', 'string', 'max:5000']],
            ["responses.{$language}.max" => 'La réponse ne doit pas dépasser 5000 caractères.'],
        );

        $check = $page->translationCheckFor($language);
        if (! $check) {
            $this->addError("responses.{$language}", 'Cette traduction n\'a pas encore été vérifiée.');

            return;
        }

        try {
            $this->service->resolve($check, auth()->user(), $status, $this->responses[$language] ?? '');
        } catch (\DomainException $e) {
            $this->addError("responses.{$language}", $e->getMessage());

            return;
        }

        session()->flash('success', $message);
    }

    private function selectedPage(): ?VerificationPage
    {
        return $this->selectedPageId
            ? VerificationPage::with(['translationChecks.checker:id,name', 'translationChecks.handler:id,name'])->find($this->selectedPageId)
            : null;
    }

    private function loadResponses(): void
    {
        $page = $this->selectedPage();

        foreach (array_keys(TranslationCheck::LANGUAGES) as $language) {
            $this->responses[$language] = $page?->translationCheckFor($language)?->admin_response ?? '';
        }
    }

    // ─── RENDU ────────────────────────────────────────────────────

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

        if ($this->selectedPageId) {
            $page = $this->selectedPage();

            if ($page) {
                if ($this->responses === []) {
                    $this->loadResponses();
                }

                return view('livewire.verification.admin.translations-manager', [
                    'view' => 'detail',
                    'page' => $page,
                    'stats' => $stats,
                    'languages' => $languages,
                ])->layout('components.layouts.app');
            }

            // Page supprimée entretemps : retour à la liste.
            $this->closePage();
        }

        // Ce qui attend une action de l'admin remonte en premier.
        $query = $this->baseQuery()
            ->with('translationChecks.checker:id,name')
            ->withCount([
                'translationChecks as to_fix_count' => fn ($q) => $q->whereIn('language', $languages)->where('status', 'to_fix'),
                'translationChecks as in_progress_count' => fn ($q) => $q->whereIn('language', $languages)->where('status', 'in_progress'),
            ])
            ->withMax('translationChecks as last_check_at', 'updated_at')
            ->orderByDesc('to_fix_count')
            ->orderByDesc('in_progress_count')
            ->orderBy('title');

        $this->service->applyFilter($query, $this->filterStatus, $languages);

        return view('livewire.verification.admin.translations-manager', [
            'view' => 'list',
            'pages' => $query->paginate(20),
            'stats' => $stats,
            'languages' => $languages,
        ])->layout('components.layouts.app');
    }
}
