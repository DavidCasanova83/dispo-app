<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApidaeService
{
    private const BASE_URL = 'https://api.apidae-tourisme.com/api/v002';

    /** Identifiant du moyen de communication "Mél" dans le référentiel Apidae. */
    private const EMAIL_TYPE_ID = 204;

    /** Nombre maximum d'objets retournés par requête par l'API Apidae. */
    private const PAGE_SIZE = 20;

    /**
     * Liste toutes les sélections du projet Apidae.
     *
     * @return array<int, array{id: int, nom: string}>
     */
    public function selections(): array
    {
        $response = $this->post('/referentiel/selections/', [
            'apiKey' => config('apidae.api_key'),
            'projetId' => (int) config('apidae.project_id'),
        ]);

        if ($response === null) {
            return [];
        }

        return collect($response)
            ->map(fn ($selection) => [
                'id' => (int) $selection['id'],
                'nom' => $selection['libelle']['libelleFr'] ?? $selection['nom'] ?? 'Sans nom',
            ])
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Récupère tous les objets touristiques d'une sélection, en paginant.
     *
     * @return array<int, array{nom: string, email: string, contacts: array<int, string>}>
     */
    public function objectsForSelection(int $selectionId): array
    {
        $objects = [];
        $first = 0;

        do {
            $response = $this->post('/recherche/list-objets-touristiques', [
                'selectionIds' => [$selectionId],
                'first' => $first,
                'count' => self::PAGE_SIZE,
                'order' => 'NOM',
                'asc' => true,
                'responseFields' => ['id', 'nom', 'informations.moyensCommunication', 'contacts'],
                'apiKey' => config('apidae.api_key'),
                'projetId' => (int) config('apidae.project_id'),
            ]);

            if ($response === null) {
                break;
            }

            $total = $response['numFound'] ?? 0;

            foreach ($response['objetsTouristiques'] ?? [] as $item) {
                $objects[] = [
                    'nom' => $item['nom']['libelleFr'] ?? 'Nom inconnu',
                    'email' => $this->extractEmail($item['informations']['moyensCommunication'] ?? []),
                    'contacts' => $this->extractContacts($item),
                ];
            }

            $first += self::PAGE_SIZE;

            // Pause courte pour ne pas saturer l'API Apidae.
            if ($first < $total) {
                usleep(100000);
            }
        } while ($first < $total);

        return $objects;
    }

    /**
     * Extrait les adresses mail des contacts rattachés à une fiche.
     *
     * @return array<int, string>
     */
    private function extractContacts(array $item): array
    {
        $emails = [];

        foreach ($item['contacts'] ?? [] as $contact) {
            $email = $this->extractEmail($contact['moyensCommunication'] ?? []);

            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    /**
     * Extrait la première adresse mail d'une liste de moyens de communication.
     *
     * @param  array<int, array<string, mixed>>  $moyensCommunication
     */
    private function extractEmail(array $moyensCommunication): string
    {
        foreach ($moyensCommunication as $moyen) {
            if (($moyen['type']['id'] ?? null) === self::EMAIL_TYPE_ID) {
                $email = trim($moyen['coordonnees']['fr'] ?? '');

                if ($email !== '') {
                    return $email;
                }
            }
        }

        return '';
    }

    /**
     * Appelle l'API Apidae, qui attend un unique paramètre "query" contenant du JSON.
     */
    private function post(string $path, array $query): ?array
    {
        try {
            $response = Http::timeout(60)
                ->asForm()
                ->post(self::BASE_URL.$path, ['query' => json_encode($query)]);

            if (! $response->successful()) {
                Log::error('Erreur API Apidae', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Exception API Apidae', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
