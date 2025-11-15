<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\QualificationStatisticsService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

class QualificationStatistics extends Component
{
    public $selectedCities = [];
    public $startDate;
    public $endDate;
    public $status = 'all';
    public $dateRange = '30d';

    public function mount()
    {
        // Par défaut : toutes les villes
        $this->selectedCities = array_keys(Qualification::getCities());

        // Par défaut : 30 derniers jours
        $this->setDateRange('30d');
    }

    public function updatedDateRange($value)
    {
        $this->setDateRange($value);
    }

    public function setDateRange($range)
    {
        $this->dateRange = $range;

        switch ($range) {
            case '7d':
                $this->startDate = now()->subDays(7)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case '30d':
                $this->startDate = now()->subDays(30)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case '3m':
                $this->startDate = now()->subMonths(3)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case '6m':
                $this->startDate = now()->subMonths(6)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case '1y':
                $this->startDate = now()->subYear()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
            // 'custom' ne fait rien, l'utilisateur définit manuellement
        }
    }

    public function getStatisticsData()
    {
        $service = new QualificationStatisticsService();

        $cities = $this->selectedCities;
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $status = $this->status;

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
        if (!$this->startDate || !$this->endDate) {
            return 'month';
        }

        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diff = $start->diffInDays($end);

        if ($diff <= 2) return 'hour';
        if ($diff <= 31) return 'day';
        if ($diff <= 90) return 'week';
        if ($diff <= 365) return 'month';
        return 'year';
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
