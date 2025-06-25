<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AccommodationRepositoryInterface
{
  public function all(): Collection;

  public function find(int $id): ?object;

  public function getFiltered(array $filters): Collection;

  public function getFilteredPaginated(array $filters, int $perPage = 100): LengthAwarePaginator;

  public function create(array $data): object;

  public function update(int $id, array $data): bool;

  public function delete(int $id): bool;

  public function getFilterOptions(): array;

  public function getStats(Collection $accommodations): array;

  public function getTopCities(Collection $accommodations, int $limit = 5): Collection;
}
