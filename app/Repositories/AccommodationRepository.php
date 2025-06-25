<?php

namespace App\Repositories;

use App\Models\Accommodation;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccommodationRepository implements AccommodationRepositoryInterface
{
  public function all(): Collection
  {
    return Accommodation::all();
  }

  public function find(int $id): ?Accommodation
  {
    return Accommodation::find($id);
  }

  public function getFiltered(array $filters): Collection
  {
    return $this->buildFilterQuery($filters)->get();
  }

  public function getFilteredPaginated(array $filters, int $perPage = 100): LengthAwarePaginator
  {
    return $this->buildFilterQuery($filters)->paginate($perPage);
  }

  public function create(array $data): Accommodation
  {
    return Accommodation::create($data);
  }

  public function update(int $id, array $data): bool
  {
    return Accommodation::findOrFail($id)->update($data);
  }

  public function delete(int $id): bool
  {
    return Accommodation::findOrFail($id)->delete();
  }

  public function getFilterOptions(): array
  {
    return [
      'statusOptions' => Accommodation::distinct()->pluck('status')->filter()->values()->toArray(),
      'cityOptions' => Accommodation::distinct()->pluck('city')->filter()->values()->sort()->toArray(),
      'typeOptions' => Accommodation::distinct()->pluck('type')->filter()->values()->sort()->toArray(),
    ];
  }

  public function getStats(Collection $accommodations): array
  {
    return [
      'total' => $accommodations->count(),
      'by_status' => $accommodations->groupBy('status')->map->count(),
      'by_type' => $accommodations->whereNotNull('type')->groupBy('type')->map->count(),
      'by_city' => $accommodations->whereNotNull('city')->groupBy('city')->map->count(),
      'with_email' => $accommodations->whereNotNull('email')->count(),
      'with_phone' => $accommodations->whereNotNull('phone')->count(),
      'with_website' => $accommodations->whereNotNull('website')->count(),
    ];
  }

  public function getTopCities(Collection $accommodations, int $limit = 5): Collection
  {
    return $accommodations->whereNotNull('city')
      ->groupBy('city')
      ->map->count()
      ->sortDesc()
      ->take($limit);
  }

  /**
   * Construit la requête de filtrage
   */
  private function buildFilterQuery(array $filters)
  {
    $query = Accommodation::query();

    // Filtre par recherche (nom)
    if (!empty($filters['search'])) {
      $query->where('name', 'like', '%' . $filters['search'] . '%');
    }

    // Filtre par statut
    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    // Filtre par ville
    if (!empty($filters['city'])) {
      $query->where('city', $filters['city']);
    }

    // Filtre par type
    if (!empty($filters['type'])) {
      $query->where('type', $filters['type']);
    }

    // Filtres pour les informations de contact
    if (!empty($filters['has_email'])) {
      $query->whereNotNull('email');
    }

    if (!empty($filters['has_phone'])) {
      $query->whereNotNull('phone');
    }

    if (!empty($filters['has_website'])) {
      $query->whereNotNull('website');
    }

    return $query->orderBy('name');
  }
}
