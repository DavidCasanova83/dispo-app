<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\QualificationStatisticsV3Service;
use App\Exports\QualificationsV3Export;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QualificationStatisticsV3 extends Component
{
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $selectedCity = 'all';
    public string $mode = 'normalized';
    public string $periodPreset = 'this_quarter';
    public string $granularity = 'auto';

    public function mount()
    {
        $this->applyPreset($this->periodPreset);
    }

    /**
     * Apply a period preset and recalculate dates.
     */
    public function applyPreset(string $preset)
    {
        $this->periodPreset = $preset;

        switch ($preset) {
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->startDate = Carbon::now()->firstOfQuarter()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                break;
            case 'last_year_same':
                $this->startDate = Carbon::now()->subYear()->firstOfQuarter()->format('Y-m-d');
                $this->endDate = Carbon::now()->subYear()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
            case 'custom':
                break;
        }

        $this->applyFilters();
    }

    public function updatedStartDate()
    {
        $this->periodPreset = 'custom';
        $this->applyFilters();
    }

    public function updatedEndDate()
    {
        $this->periodPreset = 'custom';
        $this->applyFilters();
    }

    public function updatedSelectedCity()
    {
        if ($this->selectedCity !== 'all') {
            $this->mode = 'absolute';
        } else {
            $this->mode = 'normalized';
        }
        $this->applyFilters();
    }

    public function toggleMode()
    {
        if ($this->selectedCity !== 'all') {
            return;
        }
        $this->mode = $this->mode === 'normalized' ? 'absolute' : 'normalized';
        $this->applyFilters();
    }

    public function setMode(string $mode)
    {
        if ($this->selectedCity !== 'all') {
            return;
        }
        $this->mode = $mode;
        $this->applyFilters();
    }

    public function setGranularity(string $granularity)
    {
        $this->granularity = $granularity;
        $this->applyFilters();
    }

    public function applyFilters()
    {
        $this->dispatch('v3-statistics-updated', statistics: $this->getStatisticsData());
    }

    public function getStatisticsData(): array
    {
        $service = new QualificationStatisticsV3Service();
        $effectiveMode = $this->selectedCity !== 'all' ? 'absolute' : $this->mode;
        $isSingleCity = $this->selectedCity !== 'all';

        $data = [
            'kpis' => $service->getKPIs($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'yoy' => $service->getYoYComparison($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'temporalEvolution' => $service->getTemporalEvolution($this->selectedCity, $this->startDate, $this->endDate, $this->granularity),
            'cityDistribution' => $service->getCityDistribution($this->startDate, $this->endDate),
            'generalDemands' => $service->getGeneralDemands($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'profiles' => $service->getProfileDistribution($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'ageRanges' => $service->getAgeRanges($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'geographic' => $service->getGeographicOrigin($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'contactMethods' => $service->getContactMethods($this->selectedCity, $this->startDate, $this->endDate, $effectiveMode),
            'agentActivity' => $service->getAgentActivity($this->selectedCity, $this->startDate, $this->endDate),
            'crossTabs' => $service->getCrossTabulations($this->selectedCity, $this->startDate, $this->endDate),
        ];

        // G9: only when single city
        if ($isSingleCity) {
            $data['citySpecificDemands'] = $service->getCitySpecificDemands($this->selectedCity, $this->startDate, $this->endDate);
        }

        return $data;
    }

    /**
     * Export data to Excel.
     */
    public function exportData($startDate, $endDate): BinaryFileResponse
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->greaterThan($end)) {
                $this->dispatch('export-error', message: 'La date de début doit être antérieure à la date de fin.');
                return response()->download('');
            }
        } catch (\Exception) {
            $this->dispatch('export-error', message: 'Format de date invalide.');
            return response()->download('');
        }

        $service = new QualificationStatisticsV3Service();
        $effectiveMode = $this->selectedCity !== 'all' ? 'absolute' : $this->mode;
        $city = $this->selectedCity;

        $data = [
            'kpis' => $service->getKPIs($city, $startDate, $endDate, $effectiveMode),
            'generalDemands' => $service->getGeneralDemands($city, $startDate, $endDate, 'absolute'),
            'generalDemandsNorm' => $service->getGeneralDemands($city, $startDate, $endDate, 'normalized'),
            'profiles' => $service->getProfileDistribution($city, $startDate, $endDate, 'absolute'),
            'profilesNorm' => $service->getProfileDistribution($city, $startDate, $endDate, 'normalized'),
            'ageRanges' => $service->getAgeRanges($city, $startDate, $endDate, 'absolute'),
            'ageRangesNorm' => $service->getAgeRanges($city, $startDate, $endDate, 'normalized'),
            'geographic' => $service->getGeographicOrigin($city, $startDate, $endDate, 'absolute'),
            'contactMethods' => $service->getContactMethods($city, $startDate, $endDate, 'absolute'),
            'agentActivity' => $service->getAgentActivity($city, $startDate, $endDate),
            'cityDistribution' => $service->getCityDistribution($startDate, $endDate),
        ];

        $filename = 'statistiques-v3_' . $start->format('d-m-Y') . '_au_' . $end->format('d-m-Y') . '.xlsx';

        return Excel::download(new QualificationsV3Export($data), $filename);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $cities = Qualification::getCities();
        $statistics = $this->getStatisticsData();

        return view('livewire.qualification.statistics-v3', [
            'cities' => $cities,
            'statistics' => $statistics,
            'isSingleCity' => $this->selectedCity !== 'all',
            'effectiveMode' => $this->selectedCity !== 'all' ? 'absolute' : $this->mode,
        ]);
    }
}
