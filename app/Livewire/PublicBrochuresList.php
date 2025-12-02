<?php

namespace App\Livewire;

use App\Models\Image;
use Livewire\Component;

class PublicBrochuresList extends Component
{
    public function render()
    {
        // Récupérer toutes les brochures disponibles
        // Tri: d'abord par display_order (nulls en dernier), puis par titre
        $brochures = Image::where('print_available', true)
            ->where('quantity_available', '>', 0)
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('title')
            ->get();

        return view('livewire.public-brochures-list', [
            'brochures' => $brochures,
        ])->layout('components.layouts.guest');
    }
}
