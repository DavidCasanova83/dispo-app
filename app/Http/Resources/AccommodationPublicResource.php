<?php

namespace App\Http\Resources;

use App\Services\RegroupementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $regroupementService = app(RegroupementService::class);

        return [
            'dispo_status' => $this->mapStatus($this->status),
            'partner_data' => [
                'groupement_commune' => $regroupementService->getRegroupement($this->city),
                'id_apidae' => $this->apidae_id,
                'display_name' => $this->name,
                'city' => $this->city,
                'phone' => $this->phone,
                'apidae_souscategories_data' => $this->formatSubcategories(),
            ],
        ];
    }

    /**
     * Map the status to dispo_status values.
     * "1" = Disponible, "0" = Non spécifié, "2" = Complet
     */
    protected function mapStatus(?string $status): string
    {
        return match ($status) {
            'disponible', 'active' => '1',      // Disponible
            'indisponible', 'inactive' => '2',  // Complet
            'en_attente', 'pending' => '0',     // Non spécifié
            default => '0',                      // Non spécifié par défaut
        };
    }

    /**
     * Format the type as subcategories array.
     */
    protected function formatSubcategories(): array
    {
        if (empty($this->type)) {
            return [];
        }

        return [
            ['name' => $this->type],
        ];
    }
}
