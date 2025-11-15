<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\QualificationStatisticsService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

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
        if ($this->periodFilter === '7days') {
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($this->periodFilter === '30days') {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } else {
            // Par défaut, toutes les données
            $startDate = null;
            $endDate = null;
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
