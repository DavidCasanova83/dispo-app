<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccommodationResponseController extends Controller
{
    /**
     * Handle the accommodation availability response.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function handleResponse(Request $request)
    {
        $token = $request->input('token');
        $available = filter_var($request->input('available'), FILTER_VALIDATE_BOOLEAN);

        // Vérifie que le token est fourni
        if (!$token) {
            return view('accommodation-response', [
                'success' => false,
                'message' => 'Token manquant ou invalide.',
            ]);
        }

        // Trouve l'hébergement correspondant au token
        $accommodation = Accommodation::where('email_response_token', $token)->first();

        if (!$accommodation) {
            Log::warning("Invalid token attempt: {$token}");
            return view('accommodation-response', [
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        // Met à jour la disponibilité
        $accommodation->updateAvailability($available);

        $status = $available ? 'disponible' : 'indisponible';
        Log::info("Accommodation {$accommodation->id} updated status to {$status}");

        return view('accommodation-response', [
            'success' => true,
            'message' => $available
                ? 'Merci ! Votre établissement a été marqué comme disponible.'
                : 'Merci ! Votre établissement a été marqué comme indisponible.',
            'accommodation' => $accommodation,
            'status' => $status,
        ]);
    }
}
