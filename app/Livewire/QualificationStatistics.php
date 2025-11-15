<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\QualificationStatisticsService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class QualificationStatistics extends Component
{
    public function getStatisticsData()
    {
        $service = new QualificationStatisticsService();

        // Toujours récupérer toutes les données
        $cities = []; // Toutes les villes
        $startDate = null; // Pas de limite de date début
        $endDate = null; // Pas de limite de date fin
        $status = 'all'; // Tous les statuts

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
        // Toujours grouper par mois pour une vue d'ensemble
        return 'month';
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $cities = Qualification::getCities();
        $statistics = $this->getStatisticsData();

        return view('livewire.qualification.statistics', [
            'cities' => $cities,
            'statistics' => $statistics,
        ]);
    }
}
