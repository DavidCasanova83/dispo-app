<?php

namespace App\Http\Controllers;

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
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($storagePath);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agenda-en-cours.pdf"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
