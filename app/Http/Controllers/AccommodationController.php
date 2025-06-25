<?php

namespace App\Http\Controllers;

use App\Services\AccommodationService;
use App\Http\Requests\AccommodationFilterRequest;


class AccommodationController extends Controller
{

    public function __construct(
        protected AccommodationService $accommodationService
    ) {}


    public function index(AccommodationFilterRequest $request)
    {
        $filters = $request->validated();

        $accommodations = $this->accommodationService->getFiltered($filters);
        $stats = $this->accommodationService->calculateStats($accommodations);
        $topCities = $this->accommodationService->getTopCities($accommodations);

        return view('accommodations', compact('accommodations', 'stats', 'topCities'));
    }

    /**
     * Afficher un hébergement spécifique
     */
    public function show(int $id)
    {
        $accommodation = $this->accommodationService->getAccommodationById($id);

        if (!$accommodation) {
            abort(404);
        }

        return view('accommodations.show', compact('accommodation'));
    }

    /**
     * Afficher les hébergements actifs
     */
    public function active()
    {
        $accommodations = $this->accommodationService->getActiveAccommodations();
        $stats = $this->accommodationService->calculateStats($accommodations);
        $topCities = $this->accommodationService->getTopCities($accommodations);

        return view('accommodations.active', compact('accommodations', 'stats', 'topCities'));
    }

    /**
     * Afficher les statistiques globales
     */
    public function stats()
    {
        $stats = $this->accommodationService->getGlobalStats();
        $topCities = $this->accommodationService->getGlobalTopCities();

        return view('accommodations.stats', compact('stats', 'topCities'));
    }
}
