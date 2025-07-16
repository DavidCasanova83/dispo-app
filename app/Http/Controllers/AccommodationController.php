<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccommodationFilterRequest;
use App\Models\Accommodation;
use App\Services\AccommodationService;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Show the management page for an accommodation (public route).
     */
    public function manage(string $apidae_id): View
    {
        $accommodation = Accommodation::where('apidae_id', $apidae_id)->firstOrFail();
        
        return view('accommodation.manage', compact('accommodation'));
    }

    /**
     * Update the status of an accommodation (public route).
     */
    public function updateStatus(Request $request, string $apidae_id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);
        
        $accommodation = Accommodation::where('apidae_id', $apidae_id)->firstOrFail();
        
        $oldStatus = $accommodation->status;
        $accommodation->update(['status' => $request->status]);
        
        $statusLabel = $request->status === 'active' ? 'Actif' : 'Inactif';
        
        return redirect()->back()->with('success', "Statut mis à jour avec succès : {$statusLabel}");
    }
}