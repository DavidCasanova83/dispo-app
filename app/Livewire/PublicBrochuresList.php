<?php

namespace App\Livewire;

use App\Models\Image;
use Livewire\Component;

class PublicBrochuresList extends Component
{
    public function render()
    {
        // Récupérer toutes les brochures disponibles
        $brochures = Image::where('print_available', true)
            ->where('quantity_available', '>', 0)
            ->orderBy('title')
            ->get();

        return view('livewire.public-brochures-list', [
            'brochures' => $brochures,
        ])->layout('components.layouts.guest');
    }
}
