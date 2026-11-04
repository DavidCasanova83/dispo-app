<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Adresse permanente d'une brochure : /brochure/{slug}/{langue?}.
 *
 * Redirige vers le document actuel de la langue (fichier, sinon Calaméo,
 * sinon lien), ou vers la brochure française s'il n'y a pas de version dans
 * cette langue. L'adresse ne change pas quand le PDF est remplacé : c'est
 * elle qu'on met dans les pages EN / IT de verdontourisme.com.
 *
 * Comme sur le catalogue, les brochures hors ligne et celles des auteurs
 * réservés ne sont accessibles qu'aux utilisateurs connectés.
 */
class BrochurePermalinkController extends Controller
{
    public function __invoke(string $slug, ?string $langue = null): RedirectResponse
    {
        $image = Image::where('slug', $slug)->first();
        abort_unless($image, 404);

        $main = $image->mainBrochure();
        $guest = ! Auth::check();

        if ($guest) {
            $restricted = Author::whereIn('slug', Image::RESTRICTED_AUTHOR_SLUGS)->pluck('id');
            abort_if($main->is_offline || $restricted->contains($main->author_id), 404);
        }

        $editions = $main->editions(includeOffline: ! $guest);
        $document = $editions[$langue ?? 'fr'] ?? $main;

        return redirect()->away($document->consultUrl());
    }
}
