<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgendaPdfController extends Controller
{
    /**
     * Servir le PDF de l'agenda en cours.
     * Utilise ETag et Last-Modified pour gérer le cache intelligemment.
     */
    public function current(): BinaryFileResponse
    {
        $storagePath = 'agendas/agenda-en-cours.pdf';

        if (!Storage::disk('public')->exists($storagePath)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($storagePath);
        $lastModified = Storage::disk('public')->lastModified($storagePath);
        $etag = md5($lastModified);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agenda-en-cours.pdf"',
            'Cache-Control' => 'public, must-revalidate, max-age=300',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'ETag' => '"' . $etag . '"',
        ]);
    }
}
