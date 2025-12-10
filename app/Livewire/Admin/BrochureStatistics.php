<?php

namespace App\Livewire\Admin;

use App\Models\BrochureClick;
use App\Models\Image;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BrochureStatistics extends Component
{
    public string $selectedPeriod = '1year';
    public string $periodFilter = '1year';
    public ?string $buttonTypeFilter = null;

    public function applyFilter(): void
    {
        $this->periodFilter = $this->selectedPeriod;
        $this->dispatch('statistics-updated', statistics: $this->getStatisticsData());
    }

    public function setButtonType(?string $type): void
    {
        $this->buttonTypeFilter = $type;
        $this->dispatch('statistics-updated', statistics: $this->getStatisticsData());
    }

    public function getStatisticsData(): array
    {
        $startDate = $this->calculateStartDate();
        $endDate = Carbon::now()->endOfDay();

        // Query de base
        $baseQuery = BrochureClick::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->buttonTypeFilter) {
            $baseQuery->where('button_type', $this->buttonTypeFilter);
        }

        // Total des clics
        $totalClicks = (clone $baseQuery)->count();

        // Clics par type de bouton
        $clicksByType = BrochureClick::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('button_type, COUNT(*) as count')
            ->groupBy('button_type')
            ->pluck('count', 'button_type')
            ->toArray();

        // Classement des brochures
        $brochureRankingQuery = BrochureClick::query()
            ->whereBetween('brochure_clicks.created_at', [$startDate, $endDate])
            ->join('images', 'brochure_clicks.image_id', '=', 'images.id')
            ->selectRaw('images.id, images.title, images.name, images.thumbnail_path, images.path, COUNT(*) as total_clicks')
            ->groupBy('images.id', 'images.title', 'images.name', 'images.thumbnail_path', 'images.path')
            ->orderByDesc('total_clicks');

        if ($this->buttonTypeFilter) {
            $brochureRankingQuery->where('button_type', $this->buttonTypeFilter);
        }

        $brochureRanking = $brochureRankingQuery->limit(20)->get();

        // Clics authentifiés vs anonymes
        $authenticatedClicks = BrochureClick::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($this->buttonTypeFilter, fn($q) => $q->where('button_type', $this->buttonTypeFilter))
            ->whereNotNull('user_id')
            ->count();

        $anonymousClicks = $totalClicks - $authenticatedClicks;

        return [
            'totalClicks' => $totalClicks,
            'clicksByType' => $clicksByType,
            'brochureRanking' => $brochureRanking,
            'authenticatedClicks' => $authenticatedClicks,
            'anonymousClicks' => $anonymousClicks,
            'periodLabel' => $this->getPeriodLabel(),
        ];
    }

    protected function calculateStartDate(): Carbon
    {
        return match ($this->periodFilter) {
            '7days' => Carbon::now()->subDays(7)->startOfDay(),
            '30days' => Carbon::now()->subDays(30)->startOfDay(),
            '90days' => Carbon::now()->subDays(90)->startOfDay(),
            '6months' => Carbon::now()->subMonths(6)->startOfDay(),
            '1year' => Carbon::now()->subYear()->startOfDay(),
            'all' => Carbon::parse('2020-01-01'),
            default => Carbon::now()->subYear()->startOfDay(),
        };
    }

    protected function getPeriodLabel(): string
    {
        return match ($this->periodFilter) {
            '7days' => '7 derniers jours',
            '30days' => '30 derniers jours',
            '90days' => '90 derniers jours',
            '6months' => '6 derniers mois',
            '1year' => '1 an',
            'all' => 'Toutes les données',
            default => '1 an',
        };
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.brochure-statistics', [
            'statistics' => $this->getStatisticsData(),
            'buttonTypes' => BrochureClick::BUTTON_LABELS,
        ]);
    }
}
