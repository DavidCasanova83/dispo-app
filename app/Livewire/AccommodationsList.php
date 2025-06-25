<?php

namespace App\Livewire;

use App\Models\Accommodation;
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

  public function mount()
  {
    // Charger les options pour les filtres
    $this->loadFilterOptions();
  }

  public function loadFilterOptions()
  {
    $accommodations = Accommodation::all();

    $this->statusOptions = $accommodations->pluck('status')->unique()->filter()->values()->toArray();
    $this->cityOptions = $accommodations->pluck('city')->unique()->filter()->values()->sort()->toArray();
    $this->typeOptions = $accommodations->pluck('type')->unique()->filter()->values()->sort()->toArray();
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

  public function render()
  {
    $query = Accommodation::query();

    // Filtre par recherche (nom)
    if (!empty($this->search)) {
      $query->where('name', 'like', '%' . $this->search . '%');
    }

    // Filtre par statut
    if (!empty($this->statusFilter)) {
      $query->where('status', $this->statusFilter);
    }

    // Filtre par ville
    if (!empty($this->cityFilter)) {
      $query->where('city', $this->cityFilter);
    }

    // Filtre par type
    if (!empty($this->typeFilter)) {
      $query->where('type', $this->typeFilter);
    }

    // Filtres pour les informations de contact
    if ($this->hasEmail) {
      $query->whereNotNull('email');
    }

    if ($this->hasPhone) {
      $query->whereNotNull('phone');
    }

    if ($this->hasWebsite) {
      $query->whereNotNull('website');
    }

    $accommodations = $query->orderBy('name')->paginate(100);

    // Calcul des statistiques pour les résultats filtrés
    $filteredQuery = clone $query;
    $stats = [
      'total' => $filteredQuery->count(),
      'by_status' => $filteredQuery->get()->groupBy('status')->map->count(),
      'by_type' => $filteredQuery->get()->whereNotNull('type')->groupBy('type')->map->count(),
      'by_city' => $filteredQuery->get()->whereNotNull('city')->groupBy('city')->map->count(),
      'with_email' => $filteredQuery->get()->whereNotNull('email')->count(),
      'with_phone' => $filteredQuery->get()->whereNotNull('phone')->count(),
      'with_website' => $filteredQuery->get()->whereNotNull('website')->count(),
    ];

    // Top 5 des villes pour les résultats filtrés
    $topCities = $filteredQuery->get()
      ->whereNotNull('city')
      ->groupBy('city')
      ->map->count()
      ->sortDesc()
      ->take(5);

    return view('livewire.accommodations-list', [
      'accommodations' => $accommodations,
      'stats' => $stats,
      'topCities' => $topCities,
    ]);
  }
}
