<?php

namespace App\Livewire;

use App\Services\AccommodationService;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationsList extends Component
{
    use WithPagination;

    // Filtres
    public $search = '';
    public $statusFilter = '';
    public $cityFilter = '';
    public $typeFilter = '';
    public $hasEmail = false;
    public $hasPhone = false;
    public $hasWebsite = false;

    // Options pour les filtres (chargées depuis le service)
    public $filterOptions = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'cityFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'hasEmail' => ['except' => false],
        'hasPhone' => ['except' => false],
        'hasWebsite' => ['except' => false],
    ];

    public function mount(AccommodationService $accommodationService)
    {
        // Charger les options pour les filtres depuis le service
        $this->filterOptions = $accommodationService->getFilterOptions();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedCityFilter()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedHasEmail()
    {
        $this->resetPage();
    }

    public function updatedHasPhone()
    {
        $this->resetPage();
    }

    public function updatedHasWebsite()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'search',
            'statusFilter',
            'cityFilter',
            'typeFilter',
            'hasEmail',
            'hasPhone',
            'hasWebsite'
        ]);
        $this->resetPage();
    }

    public function render(AccommodationService $accommodationService)
    {
        // Construire les filtres pour le service
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'city' => $this->cityFilter,
            'type' => $this->typeFilter,
            'has_email' => $this->hasEmail,
            'has_phone' => $this->hasPhone,
            'has_website' => $this->hasWebsite,
        ];

        // Utiliser le service pour obtenir les données optimisées
        $accommodations = $accommodationService->getFilteredAccommodations($filters);
        $stats = $accommodationService->getStatistics($filters);
        $topCities = $accommodationService->getTopCities($filters, 5);

        return view('livewire.accommodations-list', [
            'accommodations' => $accommodations,
            'stats' => $stats,
            'topCities' => $topCities,
        ]);
    }

    /**
     * Refresh filter options (useful for cache invalidation)
     */
    public function refreshFilterOptions(AccommodationService $accommodationService)
    {
        $this->filterOptions = $accommodationService->getFilterOptions();
    }

    /**
     * Get the current filters as an array
     */
    public function getCurrentFilters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'city' => $this->cityFilter,
            'type' => $this->typeFilter,
            'has_email' => $this->hasEmail,
            'has_phone' => $this->hasPhone,
            'has_website' => $this->hasWebsite,
        ];
    }

    /**
     * Check if any filter is active
     */
    public function hasActiveFilters(): bool
    {
        return !empty($this->search) ||
               !empty($this->statusFilter) ||
               !empty($this->cityFilter) ||
               !empty($this->typeFilter) ||
               $this->hasEmail ||
               $this->hasPhone ||
               $this->hasWebsite;
    }
}
