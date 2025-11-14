<?php

namespace App\Livewire;

use App\Models\Qualification;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class QualificationData extends Component
{
  use WithPagination;

  public $city;
  public $cityName;

  // Filtres
  public $search = '';
  public $completedFilter = '';
  public $dateFrom = '';
  public $dateTo = '';

  protected $queryString = [
    'search' => ['except' => ''],
    'completedFilter' => ['except' => ''],
    'dateFrom' => ['except' => ''],
    'dateTo' => ['except' => ''],
  ];

  public function mount($city, $cityName)
  {
    $this->city = $city;
    $this->cityName = $cityName;
  }

  public function updatedSearch()
  {
    $this->resetPage();
  }

  public function updatedCompletedFilter()
  {
    $this->resetPage();
  }

  public function updatedDateFrom()
  {
    $this->resetPage();
  }

  public function updatedDateTo()
  {
    $this->resetPage();
  }

  public function clearFilters()
  {
    $this->reset(['search', 'completedFilter', 'dateFrom', 'dateTo']);
    $this->resetPage();
  }

  public function delete($qualificationId)
  {
    $qualification = Qualification::findOrFail($qualificationId);
    $qualification->delete();

    session()->flash('success', 'Entrée supprimée avec succès');
  }

  #[Layout('components.layouts.app')]
  public function render()
  {
    $query = Qualification::query()
      ->where('city', $this->city)
      ->with('user');

    // Filtre par recherche (email)
    if (!empty($this->search)) {
      $query->where(function ($q) {
        $q->whereJsonContains('form_data->email', $this->search)
          ->orWhereHas('user', function ($userQuery) {
            $userQuery->where('name', 'like', '%' . $this->search . '%')
              ->orWhere('email', 'like', '%' . $this->search . '%');
          });
      });
    }

    // Filtre par statut de complétion
    if ($this->completedFilter !== '') {
      $query->where('completed', (bool)$this->completedFilter);
    }

    // Filtre par date
    if (!empty($this->dateFrom)) {
      $query->whereDate('created_at', '>=', $this->dateFrom);
    }

    if (!empty($this->dateTo)) {
      $query->whereDate('created_at', '<=', $this->dateTo);
    }

    $qualifications = $query->orderBy('created_at', 'desc')->paginate(20);

    // Statistiques
    $stats = [
      'total' => Qualification::where('city', $this->city)->count(),
      'completed' => Qualification::where('city', $this->city)->completed()->count(),
      'incomplete' => Qualification::where('city', $this->city)->incomplete()->count(),
      'today' => Qualification::where('city', $this->city)
        ->whereDate('created_at', today())
        ->count(),
    ];

    return view('livewire.qualification-data', [
      'qualifications' => $qualifications,
      'stats' => $stats,
    ]);
  }
}
