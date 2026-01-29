<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgendaPdfController extends Controller
{
    /**
     * Servir le PDF de l'agenda en cours avec cache de 5 minutes.
     */
    public function current(): BinaryFileResponse
    {
        $storagePath = 'agendas/agenda-en-cours.pdf';

        if (!Storage::disk('public')->exists($storagePath)) {
            Log::warning('[AGENDA] Téléchargement PDF - Fichier non trouvé', [
                'path' => $storagePath,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($storagePath);

        Log::info('[AGENDA] Téléchargement PDF courant', [
            'ip' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 100),
        ]);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agenda-en-cours.pdf"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
