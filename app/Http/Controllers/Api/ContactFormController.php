<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactFormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactFormController extends Controller
{
    /**
     * Store a new contact form submission from WordPress CF7.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'form_id' => 'required|integer',
            // Visiteur
            'visiteur.nom' => 'required|string|max:255',
            'visiteur.prenom' => 'required|string|max:255',
            'visiteur.email' => 'required|email|max:255',
            'visiteur.telephone' => 'nullable|string|max:50',
            'visiteur.message' => 'required|string|max:10000',
            // Etablissement
            'etablissement.id_apidae' => 'nullable|string|max:50',
            'etablissement.nom' => 'nullable|string|max:255',
            'etablissement.email' => 'nullable|email|max:255',
            // Metadata
            'metadata.url_page' => 'nullable|url|max:500',
            'metadata.date_soumission' => 'nullable|date',
            'metadata.ip_visiteur' => 'nullable|string|max:45',
            'metadata.user_agent' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        $submission = ContactFormSubmission::create([
            'form_id' => $data['form_id'],
            // Visiteur
            'visiteur_nom' => $data['visiteur']['nom'],
            'visiteur_prenom' => $data['visiteur']['prenom'],
            'visiteur_email' => $data['visiteur']['email'],
            'visiteur_telephone' => $data['visiteur']['telephone'] ?? null,
            'visiteur_message' => $data['visiteur']['message'],
            // Etablissement
            'etablissement_apidae_id' => $data['etablissement']['id_apidae'] ?? null,
            'etablissement_nom' => $data['etablissement']['nom'] ?? null,
            'etablissement_email' => $data['etablissement']['email'] ?? null,
            // Metadata
            'url_page' => $data['metadata']['url_page'] ?? null,
            'date_soumission' => $data['metadata']['date_soumission'] ?? now(),
            'ip_visiteur' => $data['metadata']['ip_visiteur'] ?? $request->ip(),
            'user_agent' => $data['metadata']['user_agent'] ?? $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submission received successfully',
            'id' => $submission->id
        ], 201);
    }
}
