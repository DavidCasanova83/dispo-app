<?php

namespace App\Services;

use App\Repositories\AccommodationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccommodationService
{
    public function __construct(
        protected AccommodationRepositoryInterface $accommodationRepository
    ) {}

    public function getAllOrdered(): Collection
    {
        return $this->accommodationRepository->all();
    }

    public function getFiltered(array $filters): Collection
    {
        return $this->accommodationRepository->getFiltered($filters);
    }

    public function getFilteredPaginated(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->accommodationRepository->getFilteredPaginated($filters, $perPage);
    }

    public function getFilterOptions(): array
    {
        return $this->accommodationRepository->getFilterOptions();
    }

    public function calculateStats(Collection $accommodations): array
    {
        return $this->accommodationRepository->getStats($accommodations);
    }

    public function getTopCities(Collection $accommodations, int $limit = 5): Collection
    {
        return $this->accommodationRepository->getTopCities($accommodations, $limit);
    }

    public function getFilteredStats(array $filters): array
    {
        $filteredAccommodations = $this->accommodationRepository->getFiltered($filters);
        return $this->accommodationRepository->getStats($filteredAccommodations);
    }

    public function getFilteredTopCities(array $filters, int $limit = 5): Collection
    {
        $filteredAccommodations = $this->accommodationRepository->getFiltered($filters);
        return $this->accommodationRepository->getTopCities($filteredAccommodations, $limit);
    }

    // Méthodes utilitaires pour simplifier l'usage

    /**
     * Récupérer un hébergement par son ID
     */
    public function getAccommodationById(int $id)
    {
        return $this->accommodationRepository->find($id);
    }

    /**
     * Créer un nouvel hébergement
     */
    public function createAccommodation(array $data)
    {
        return $this->accommodationRepository->create($data);
    }

    /**
     * Mettre à jour un hébergement
     */
    public function updateAccommodation(int $id, array $data): bool
    {
        return $this->accommodationRepository->update($id, $data);
    }

    /**
     * Supprimer un hébergement
     */
    public function deleteAccommodation(int $id): bool
    {
        return $this->accommodationRepository->delete($id);
    }

    /**
     * Récupérer les hébergements actifs
     */
    public function getActiveAccommodations(): Collection
    {
        return $this->accommodationRepository->getFiltered(['status' => 'active']);
    }

    /**
     * Récupérer les hébergements par ville
     */
    public function getAccommodationsByCity(string $city): Collection
    {
        return $this->accommodationRepository->getFiltered(['city' => $city]);
    }

    /**
     * Récupérer les hébergements par type
     */
    public function getAccommodationsByType(string $type): Collection
    {
        return $this->accommodationRepository->getFiltered(['type' => $type]);
    }

    /**
     * Récupérer les hébergements avec email
     */
    public function getAccommodationsWithEmail(): Collection
    {
        return $this->accommodationRepository->getFiltered(['has_email' => true]);
    }

    /**
     * Récupérer les hébergements avec téléphone
     */
    public function getAccommodationsWithPhone(): Collection
    {
        return $this->accommodationRepository->getFiltered(['has_phone' => true]);
    }

    /**
     * Récupérer les hébergements avec site web
     */
    public function getAccommodationsWithWebsite(): Collection
    {
        return $this->accommodationRepository->getFiltered(['has_website' => true]);
    }

    /**
     * Rechercher des hébergements par nom
     */
    public function searchAccommodationsByName(string $search): Collection
    {
        return $this->accommodationRepository->getFiltered(['search' => $search]);
    }

    /**
     * Obtenir les statistiques globales
     */
    public function getGlobalStats(): array
    {
        $allAccommodations = $this->accommodationRepository->all();
        return $this->accommodationRepository->getStats($allAccommodations);
    }

    /**
     * Obtenir les top villes globales
     */
    public function getGlobalTopCities(int $limit = 5): Collection
    {
        $allAccommodations = $this->accommodationRepository->all();
        return $this->accommodationRepository->getTopCities($allAccommodations, $limit);
    }
}
