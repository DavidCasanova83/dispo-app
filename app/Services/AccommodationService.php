<?php

namespace App\Services;

use App\Models\Accommodation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccommodationService
{
    private const CACHE_TTL = 3600; // 1 heure

    /**
     * Get filtered accommodations with pagination
     */
    public function getFilteredAccommodations(array $filters): LengthAwarePaginator
    {
        return Accommodation::query()
            ->when($filters['search'] ?? null, fn($q, $search) => 
                $q->search($search)
            )
            ->when($filters['status'] ?? null, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->when($filters['city'] ?? null, fn($q, $city) => 
                $q->byCity($city)
            )
            ->when($filters['type'] ?? null, fn($q, $type) => 
                $q->byType($type)
            )
            ->when($filters['has_email'] ?? false, fn($q) => 
                $q->whereNotNull('email')
            )
            ->when($filters['has_phone'] ?? false, fn($q) => 
                $q->whereNotNull('phone')
            )
            ->when($filters['has_website'] ?? false, fn($q) => 
                $q->whereNotNull('website')
            )
            ->orderBy('name')
            ->paginate(100);
    }

    /**
     * Get optimized statistics for accommodations
     */
    public function getStatistics(array $filters = []): array
    {
        $cacheKey = 'accommodation_stats_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $baseQuery = $this->buildBaseQuery($filters);
            
            // Use single optimized queries with raw SQL for better performance
            $totalCount = $baseQuery->count();
            
            $statusStats = $baseQuery
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $typeStats = $baseQuery
                ->select('type', DB::raw('COUNT(*) as count'))
                ->whereNotNull('type')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $cityStats = $baseQuery
                ->select('city', DB::raw('COUNT(*) as count'))
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderByDesc('count')
                ->take(10)
                ->pluck('count', 'city')
                ->toArray();

            // Contact information statistics with single queries
            $contactStats = $baseQuery
                ->selectRaw('
                    SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as with_email,
                    SUM(CASE WHEN phone IS NOT NULL THEN 1 ELSE 0 END) as with_phone,
                    SUM(CASE WHEN website IS NOT NULL THEN 1 ELSE 0 END) as with_website
                ')
                ->first();

            return [
                'total' => $totalCount,
                'by_status' => $statusStats,
                'by_type' => $typeStats,
                'by_city' => $cityStats,
                'with_email' => $contactStats->with_email ?? 0,
                'with_phone' => $contactStats->with_phone ?? 0,
                'with_website' => $contactStats->with_website ?? 0,
            ];
        });
    }

    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('accommodation_filter_options', self::CACHE_TTL, function () {
            return [
                'cities' => Accommodation::distinct()
                    ->whereNotNull('city')
                    ->orderBy('city')
                    ->pluck('city')
                    ->values()
                    ->toArray(),
                'types' => Accommodation::distinct()
                    ->whereNotNull('type')
                    ->orderBy('type')
                    ->pluck('type')
                    ->values()
                    ->toArray(),
                'statuses' => ['pending', 'active', 'inactive'],
            ];
        });
    }

    /**
     * Get top cities with accommodation count
     */
    public function getTopCities(array $filters = [], int $limit = 5): array
    {
        $cacheKey = 'top_cities_' . md5(serialize($filters)) . "_{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $limit) {
            return $this->buildBaseQuery($filters)
                ->select('city', DB::raw('COUNT(*) as count'))
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderByDesc('count')
                ->take($limit)
                ->pluck('count', 'city')
                ->toArray();
        });
    }

    /**
     * Find accommodation by ID
     */
    public function findById(int $id): ?Accommodation
    {
        return Accommodation::find($id);
    }

    /**
     * Get accommodations with contact information
     */
    public function getWithContactInfo(): Collection
    {
        return Accommodation::withContact()->get();
    }

    /**
     * Get recent accommodations
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Accommodation::orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Search accommodations by name
     */
    public function searchByName(string $search, int $limit = 50): Collection
    {
        return Accommodation::search($search)
            ->take($limit)
            ->get();
    }

    /**
     * Clear all accommodation-related cache
     */
    public function clearCache(): void
    {
        Cache::forget('accommodation_filter_options');
        
        // Clear all stats cache (pattern-based clearing)
        $cacheKeys = [
            'accommodation_stats_',
            'top_cities_'
        ];
        
        foreach ($cacheKeys as $pattern) {
            // Note: In production, consider using cache tags for better cache management
            Cache::flush(); // For now, flush all cache
        }
    }

    /**
     * Update accommodation cache after data changes
     */
    public function refreshCache(): void
    {
        $this->clearCache();
        
        // Warm up frequently used cache
        $this->getFilterOptions();
        $this->getStatistics();
        $this->getTopCities();
    }

    /**
     * Build base query for filtering
     */
    private function buildBaseQuery(array $filters)
    {
        return Accommodation::query()
            ->when($filters['search'] ?? null, fn($q, $search) => 
                $q->search($search)
            )
            ->when($filters['status'] ?? null, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->when($filters['city'] ?? null, fn($q, $city) => 
                $q->byCity($city)
            )
            ->when($filters['type'] ?? null, fn($q, $type) => 
                $q->byType($type)
            )
            ->when($filters['has_email'] ?? false, fn($q) => 
                $q->whereNotNull('email')
            )
            ->when($filters['has_phone'] ?? false, fn($q) => 
                $q->whereNotNull('phone')
            )
            ->when($filters['has_website'] ?? false, fn($q) => 
                $q->whereNotNull('website')
            );
    }
}