<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\AccommodationResponse;
use App\Services\MailjetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AccommodationResponseController extends Controller
{
    /**
     * Affiche la page de confirmation (GET avec URL signée).
     * Les scanners d'emails n'exécutent que des GET → aucune action en BDD.
     */
    public function showConfirmation(Request $request, Accommodation $accommodation)
    {
        $available = filter_var($request->input('available'), FILTER_VALIDATE_BOOLEAN);

        return view('accommodation-response-confirm', [
            'accommodation' => $accommodation,
            'available' => $available,
            'fullUrl' => $request->fullUrl(),
        ]);
    }

    /**
     * Enregistre la réponse de disponibilité (POST avec URL signée + CSRF).
     */
    public function processResponse(Request $request, Accommodation $accommodation)
    {
        $available = filter_var($request->input('available'), FILTER_VALIDATE_BOOLEAN);
        $status = $available ? 'disponible' : 'indisponible';

        // Idempotence : si une réponse identique existe déjà aujourd'hui, on affiche directement le succès
        $alreadyResponded = $accommodation->responses()
            ->whereDate('created_at', today())
            ->where('is_available', $available)
            ->exists();

        if (!$alreadyResponded) {
            $accommodation->updateAvailability(
                $available,
                null,
                $request->ip(),
                $request->userAgent()
            );

            Log::info("Accommodation {$accommodation->id} ({$accommodation->name}) updated status to {$status}", [
                'accommodation_id' => $accommodation->id,
                'name' => $accommodation->name,
                'email' => $accommodation->email,
                'status' => $status,
                'ip' => $request->ip(),
            ]);
        }

        return view('accommodation-response-success', [
            'accommodation' => $accommodation,
            'status' => $status,
        ]);
    }

    /**
     * Fallback pour les anciens liens avec token (rétrocompatibilité).
     */
    public function handleResponseLegacy(Request $request)
    {
        $token = $request->input('token');
        $available = filter_var($request->input('available'), FILTER_VALIDATE_BOOLEAN);

        if (!$token) {
            return view('accommodation-response', [
                'success' => false,
                'message' => 'Token manquant ou invalide.',
            ]);
        }

        $accommodation = Accommodation::where('email_response_token', $token)->first();

        if (!$accommodation) {
            // TODO: Supprimer ce fallback historique après le 2026-03-15 (fin de transition legacy)
            // Chercher dans l'historique des réponses pour retrouver l'hébergement
            $historicResponse = AccommodationResponse::where('response_token', $token)->first();

            if ($historicResponse) {
                $redirectUrl = URL::temporarySignedRoute('accommodation.response', now()->addDays(7), [
                    'accommodation' => $historicResponse->accommodation_id,
                    'available' => $available ? 1 : 0,
                ]);

                Log::info("Legacy token matched via history, redirecting", [
                    'accommodation_id' => $historicResponse->accommodation_id,
                    'ip' => $request->ip(),
                ]);

                return redirect($redirectUrl);
            }

            Log::info("Invalid token attempt (ancien lien)", [
                'token' => $token,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return view('accommodation-response', [
                'success' => false,
                'message' => 'Ce lien n\'est plus valide. Un nouvel email vous est envoyé automatiquement chaque matin à 6h. Veuillez utiliser le lien contenu dans le dernier email reçu.',
            ]);
        }

        $accommodation->updateAvailability(
            $available,
            $token,
            $request->ip(),
            $request->userAgent()
        );

        $status = $available ? 'disponible' : 'indisponible';
        Log::info("Accommodation {$accommodation->id} updated status to {$status} (legacy token)");

        return view('accommodation-response', [
            'success' => true,
            'message' => $available
                ? 'Merci ! Votre établissement a été marqué comme disponible.'
                : 'Merci ! Votre établissement a été marqué comme indisponible.',
            'accommodation' => $accommodation,
            'status' => $status,
        ]);
    }

    /**
     * Envoie un signalement de problème par un hébergeur au webmaster.
     */
    public function reportProblem(Request $request, MailjetService $mailjetService)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
            'accommodation_name' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:500',
        ]);

        $result = $mailjetService->sendAccommodationProblemReport(
            $validated['accommodation_name'] ?? 'Non identifié',
            $validated['comment'] ?? '',
            $validated['page_url'] ?? 'Inconnue',
            $request->ip(),
            $request->userAgent()
        );

        if ($result['success']) {
            Log::info("Accommodation problem report sent", [
                'accommodation_name' => $validated['accommodation_name'] ?? 'N/A',
                'ip' => $request->ip(),
            ]);
        }

        return view('accommodation-report-sent');
    }
}
