<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\QualificationStatisticsService;
use App\Exports\QualificationsExport;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QualificationStatisticsV2 extends Component
{
    public $selectedPeriod = '30days'; // Période sélectionnée dans le formulaire
    public $periodFilter = '30days'; // Période actuellement appliquée

    public function applyFilter()
    {
        $this->periodFilter = $this->selectedPeriod;

        // Émettre un événement avec les nouvelles données pour mettre à jour les graphiques
        $this->dispatch('statistics-updated', statistics: $this->getStatisticsData());
    }

    public function getStatisticsData()
    {
        $service = new QualificationStatisticsService();

        $cities = array_keys(Qualification::getCities());

        // Définir les dates selon le filtre appliqué
        switch ($this->periodFilter) {
            case '7days':
                $startDate = Carbon::now()->subDays(7)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case '30days':
                $startDate = Carbon::now()->subDays(30)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case '90days':
                $startDate = Carbon::now()->subDays(90)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case '180days':
                $startDate = Carbon::now()->subDays(180)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'all':
            default:
                // Toutes les données
                $startDate = null;
                $endDate = null;
                break;
        }

        $status = 'all';

        return [
            'kpis' => $service->getKPIs($cities, $startDate, $endDate, $status),
            'cityStats' => $service->getStatsByCity($startDate, $endDate, $status),
            'temporalEvolution' => $service->getTemporalEvolution($cities, $startDate, $endDate, $status, $this->getGroupBy()),
            'geographic' => $service->getGeographicStats($cities, $startDate, $endDate, $status),
            'profiles' => $service->getProfileStats($cities, $startDate, $endDate, $status),
            'demands' => $service->getDemandStats($cities, $startDate, $endDate, $status),
            'contact' => $service->getContactStats($cities, $startDate, $endDate, $status),
        ];
    }

    protected function getGroupBy()
    {
        // Adapter le groupement selon la période
        if ($this->periodFilter === '7days') {
            return 'day';
        }
        return 'day';
    }

    /**
     * Exporter les données avec des dates personnalisées
     */
    public function exportData($startDate, $endDate): BinaryFileResponse
    {
        // Validation des dates
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->greaterThan($end)) {
                $this->dispatch('export-error', message: 'La date de début doit être antérieure ou égale à la date de fin.');
                return response()->download('');
            }
        } catch (\Exception) {
            $this->dispatch('export-error', message: 'Format de date invalide.');
            return response()->download('');
        }

        // Préparation des filtres pour l'export
        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => 'all', // Exporter tous les statuts
        ];

        // Génération du nom de fichier avec les dates
        $filename = 'qualifications_' . $start->format('d-m-Y') . '_au_' . $end->format('d-m-Y') . '.xlsx';

        // Export via Excel
        return Excel::download(new QualificationsExport($filters), $filename);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $cities = Qualification::getCities();
        $statistics = $this->getStatisticsData();

        return view('livewire.qualification.statistics-v2', [
            'cities' => $cities,
            'statistics' => $statistics,
        ]);
    }
}
