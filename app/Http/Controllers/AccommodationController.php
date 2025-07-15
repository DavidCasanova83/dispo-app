<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccommodationFilterRequest;
use App\Services\AccommodationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccommodationController extends Controller
{
    public function __construct(
        private AccommodationService $accommodationService
    ) {}

    /**
     * Display a listing of accommodations with filters and statistics.
     */
    public function index(AccommodationFilterRequest $request): View
    {
        $filters = $request->validated();
        
        $accommodations = $this->accommodationService->getFilteredAccommodations($filters);
        $stats = $this->accommodationService->getStatistics($filters);
        $filterOptions = $this->accommodationService->getFilterOptions();
        
        // Calculate top cities for display
        $topCities = $this->accommodationService->getTopCities($filters, 5);

        return view('accommodations.index', compact(
            'accommodations', 
            'stats', 
            'filterOptions',
            'topCities',
            'filters'
        ));
    }

    /**
     * Show the form for creating a new accommodation.
     */
    public function create(): View
    {
        return view('accommodations.create');
    }

    /**
     * Show the specified accommodation.
     */
    public function show(int $id): View
    {
        $accommodation = $this->accommodationService->findById($id);
        
        if (!$accommodation) {
            abort(404);
        }

        return view('accommodations.show', compact('accommodation'));
    }
}