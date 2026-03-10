<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgendaPdfController extends Controller
{
    /**
     * Servir le PDF de l'agenda en cours.
     * Lookup dynamique depuis la BDD avec validation de cache ETag.
     */
    public function current(): BinaryFileResponse
    {
        $agenda = Agenda::current()->first();

        if (!$agenda || !$agenda->pdf_path) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($agenda->pdf_path)) {
            abort(404);
        }

        $fullPath = $disk->path($agenda->pdf_path);
        $lastModified = $disk->lastModified($agenda->pdf_path);
        $etag = md5($agenda->id . '-' . $lastModified);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agenda-en-cours.pdf"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'ETag' => '"' . $etag . '"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ]);
    }
}
