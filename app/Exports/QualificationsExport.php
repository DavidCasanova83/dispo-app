<?php

namespace App\Exports;

use App\Models\Qualification;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class QualificationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithChunkReading
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query pour l'export
     */
    public function query()
    {
        $query = Qualification::with('user');

        // Filtre par villes
        if (!empty($this->filters['cities'])) {
            $query->whereIn('city', $this->filters['cities']);
        }

        // Filtre par période
        if (!empty($this->filters['startDate'])) {
            $query->where('created_at', '>=', Carbon::parse($this->filters['startDate'])->startOfDay());
        }

        if (!empty($this->filters['endDate'])) {
            $query->where('created_at', '<=', Carbon::parse($this->filters['endDate'])->endOfDay());
        }

        // Filtre par statut
        if (isset($this->filters['status']) && $this->filters['status'] !== 'all') {
            if ($this->filters['status'] === 'completed') {
                $query->where('completed', true);
            } elseif ($this->filters['status'] === 'incomplete') {
                $query->where('completed', false);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Taille du chunk pour le traitement par lots
     */
    public function chunkSize(): int
    {
        return 500; // Traiter 500 enregistrements à la fois
    }

    /**
     * En-têtes des colonnes
     */
    public function headings(): array
    {
        return [
            'ID',
            'Ville',
            'Statut',
            'Date de création',
            'Date de complétion',
            'Étape actuelle',
            'Nom de l\'agent',
            'Email de l\'agent',
            'Pays',
            'Département(s)',
            'Département inconnu',
            'Email visiteur',
            'Consent Newsletter',
            'Consent Traitement données',
            'Profil visiteur',
            'Tranches d\'âge',
            'Date de modification',
            'Méthode de contact',
            'Demandes spécifiques ville',
            'Autres demandes spécifiques',
            'Demandes générales',
            'Demande texte libre',
        ];
    }

    /**
     * Mapping des données
     */
    public function map($qualification): array
    {
        $formData = $qualification->form_data ?? [];

        // Ville formatée
        $cityName = Qualification::getCities()[$qualification->city] ?? $qualification->city;

        // Départements (array to string)
        $departments = isset($formData['departments']) && is_array($formData['departments'])
            ? implode(', ', $formData['departments'])
            : '';

        // Tranches d'âge (array to string)
        $ageGroups = isset($formData['ageGroups']) && is_array($formData['ageGroups'])
            ? implode(', ', $formData['ageGroups'])
            : '';

        // Demandes spécifiques (array to string)
        $specificRequests = isset($formData['specificRequests']) && is_array($formData['specificRequests'])
            ? implode(', ', $formData['specificRequests'])
            : '';

        // Autres demandes spécifiques (array to string)
        $otherSpecificRequests = isset($formData['otherSpecificRequests']) && is_array($formData['otherSpecificRequests'])
            ? implode(', ', $formData['otherSpecificRequests'])
            : '';

        // Demandes générales (array to string)
        $generalRequests = isset($formData['generalRequests']) && is_array($formData['generalRequests'])
            ? implode(', ', $formData['generalRequests'])
            : '';

        return [
            $qualification->id,
            $cityName,
            $qualification->completed ? 'Complété' : 'En cours',
            $qualification->created_at ? $qualification->created_at->format('d/m/Y H:i') : '',
            $qualification->completed_at ? $qualification->completed_at->format('d/m/Y H:i') : '',
            $qualification->current_step,
            $qualification->user->name ?? '',
            $qualification->user->email ?? '',
            $formData['country'] ?? '',
            $departments,
            ($formData['departmentUnknown'] ?? false) ? 'Oui' : 'Non',
            $formData['email'] ?? '',
            ($formData['consentNewsletter'] ?? false) ? 'Oui' : 'Non',
            ($formData['consentDataProcessing'] ?? false) ? 'Oui' : 'Non',
            $formData['profile'] ?? '',
            $ageGroups,
            $formData['addedDate'] ?? '',
            $formData['contactMethod'] ?? '',
            $specificRequests,
            $otherSpecificRequests,
            $generalRequests,
            $formData['otherRequest'] ?? '',
        ];
    }

    /**
     * Styles pour le fichier Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // En-têtes en gras
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Événements pour formatage avancé
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Appliquer un filtre automatique
                $event->sheet->getDelegate()->setAutoFilter('A1:V1');

                // Figer la première ligne
                $event->sheet->getDelegate()->freezePane('A2');

                // Couleur de fond pour les en-têtes
                $event->sheet->getDelegate()->getStyle('A1:V1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '3E9B90']
                    ],
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'],
                        'bold' => true,
                    ],
                ]);
            },
        ];
    }
}
