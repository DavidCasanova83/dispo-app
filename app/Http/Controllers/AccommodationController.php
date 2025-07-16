<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccommodationFilterRequest;
use App\Jobs\SendAccommodationNotificationEmails;
use App\Models\Accommodation;
use App\Models\ActivityLog;
use App\Services\AccommodationService;
use Illuminate\Http\JsonResponse;
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
        
        // Log the access to the management page
        ActivityLog::logActivity(
            'status_change',
            'management_page_accessed',
            'accommodation',
            $accommodation->apidae_id,
            [
                'accommodation_name' => $accommodation->name,
                'accommodation_status' => $accommodation->status,
            ],
            "Page de gestion consultée pour l'hébergement: {$accommodation->name}"
        );
        
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
        $newStatus = $request->status;
        
        // Update the status
        $accommodation->update(['status' => $newStatus]);
        
        // Log the status change
        ActivityLog::logActivity(
            'status_change',
            $newStatus === 'active' ? 'activated' : 'deactivated',
            'accommodation',
            $accommodation->apidae_id,
            [
                'accommodation_name' => $accommodation->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            "Statut de l'hébergement '{$accommodation->name}' changé de '{$oldStatus}' vers '{$newStatus}'"
        );
        
        $statusLabel = $newStatus === 'active' ? 'Actif' : 'Inactif';
        
        return redirect()->back()->with('success', "Statut mis à jour avec succès : {$statusLabel}");
    }

    /**
     * Send notification emails to all accommodations (manual trigger).
     */
    public function sendEmails(): JsonResponse
    {
        try {
            // Get count of accommodations with emails
            $accommodationsWithEmails = Accommodation::whereNotNull('email')
                ->where('email', '!=', '')
                ->count();

            if ($accommodationsWithEmails === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun hébergement avec adresse email trouvé'
                ]);
            }

            // Log the manual trigger
            ActivityLog::logActivity(
                'email_notification',
                'manual_trigger',
                'system',
                null,
                [
                    'accommodations_count' => $accommodationsWithEmails,
                    'user_id' => auth()->user()->id,
                    'user_name' => auth()->user()->name,
                ],
                "Envoi d'emails déclenché manuellement par " . auth()->user()->name . " pour {$accommodationsWithEmails} hébergements"
            );

            // Dispatch the email job
            SendAccommodationNotificationEmails::dispatch();

            return response()->json([
                'success' => true,
                'message' => "Job d'envoi d'emails dispatché avec succès pour {$accommodationsWithEmails} hébergements. Consultez les logs pour suivre le progrès."
            ]);

        } catch (\Exception $e) {
            // Log the error
            $user = auth()->user();
            ActivityLog::logActivity(
                'email_notification',
                'manual_trigger_error',
                'system',
                null,
                [
                    'error' => $e->getMessage(),
                    'user_id' => $user ? $user->id : null,
                    'user_name' => $user ? $user->name : 'Unknown',
                ],
                "Erreur lors du déclenchement manuel d'emails par " . ($user ? $user->name : 'Unknown') . ": " . $e->getMessage(),
                'error'
            );

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déclenchement de l\'envoi des emails: ' . $e->getMessage()
            ]);
        }
    }
}