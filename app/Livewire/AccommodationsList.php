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
  public $sortBy = 'name'; // name, responses_desc, responses_asc

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
    'sortBy' => ['except' => 'name'],
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

  public function updatedSortBy()
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
      'hasWebsite',
      'sortBy'
    ]);
    $this->resetPage();
  }

  public function sendAvailabilityEmails()
  {
    // Récupère tous les hébergements qui ont une adresse email
    $accommodations = Accommodation::whereNotNull('email')
      ->where('email', '!=', '')
      ->get();

    if ($accommodations->isEmpty()) {
      session()->flash('error', 'Aucun hébergement avec email trouvé.');
      return;
    }

    // Dispatch un job pour chaque hébergement
    foreach ($accommodations as $accommodation) {
      \App\Jobs\SendAccommodationAvailabilityEmail::dispatch($accommodation);
    }

    session()->flash('success', "Envoi de {$accommodations->count()} emails en cours...");
  }

  public function updateStatus($accommodationId, $status)
  {
    $accommodation = Accommodation::findOrFail($accommodationId);

    // Utiliser updateAvailability pour bénéficier de la logique "1 réponse/jour"
    $accommodation->updateAvailability(
      $status === 'disponible',
      null,
      request()->ip(),
      'Manual update via admin panel'
    );

    session()->flash('success', "Statut de \"{$accommodation->name}\" mis à jour : {$this->getStatusLabel($status)}");
  }

  public function getStatusLabel($status)
  {
    $labels = [
      // Valeurs françaises (actuelles)
      'disponible' => 'Disponible',
      'indisponible' => 'Non disponible',
      'en_attente' => 'En attente',
      // Valeurs anglaises (legacy)
      'active' => 'Disponible',
      'inactive' => 'Non disponible',
      'pending' => 'En attente',
    ];

    return $labels[$status] ?? ucfirst($status);
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

    $query->withCount([
      'responses',
      'responses as available_responses_count' => function ($query) {
        $query->where('is_available', true);
      },
      'responses as unavailable_responses_count' => function ($query) {
        $query->where('is_available', false);
      },
    ]);

    // Tri selon le choix de l'utilisateur
    if ($this->sortBy === 'responses_desc') {
      $query->orderBy('responses_count', 'desc');
    } elseif ($this->sortBy === 'responses_asc') {
      $query->orderBy('responses_count', 'asc');
    } else {
      $query->orderBy('name');
    }

    $accommodations = $query->paginate(100);

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
