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

  // Options pour les filtres
  public $statusOptions = [];
  public $cityOptions = [];
  public $typeOptions = [];

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
    // Charger les options pour les filtres
    $this->loadFilterOptions($accommodationService);
  }

  public function loadFilterOptions(AccommodationService $accommodationService)
  {
    $filterOptions = $accommodationService->getFilterOptions();

    $this->statusOptions = $filterOptions['statusOptions'];
    $this->cityOptions = $filterOptions['cityOptions'];
    $this->typeOptions = $filterOptions['typeOptions'];
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
    // Préparer les filtres
    $filters = [
      'search' => $this->search,
      'status' => $this->statusFilter,
      'city' => $this->cityFilter,
      'type' => $this->typeFilter,
      'has_email' => $this->hasEmail,
      'has_phone' => $this->hasPhone,
      'has_website' => $this->hasWebsite,
    ];

    // Récupérer les données via le service
    $accommodations = $accommodationService->getFilteredPaginated($filters, 100);
    $stats = $accommodationService->getFilteredStats($filters);
    $topCities = $accommodationService->getFilteredTopCities($filters, 5);

    return view('livewire.accommodations-list', [
      'accommodations' => $accommodations,
      'stats' => $stats,
      'topCities' => $topCities,
    ]);
  }
}
