<?php

namespace App\Filament\Resources\Tags\Widgets;

use App\Models\Visit;
use Illuminate\Support\Collection;

class TagVisitsByBrowserPieChart extends BaseTagVisitsPieChart
{
    protected ?string $chartHeading = 'Visitors By Browser';

    protected ?int $limit = 8; // Show top 8 browsers instead of default 10

    /**
     * Override getData to implement browser name normalization
     */
    protected function getData(): array
    {
        $query = Visit::query()->whereIn('link_id', $this->record->links()->select('links.id'));

        // Apply date filters
        $query = $this->applyDateFilter($query);

        // Get the raw browser data
        $rawData = $query->selectRaw('count(*) as total, browser')
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->get();

        // If no data is available, return empty state
        if ($rawData->isEmpty()) {
            return $this->getEmptyStateData();
        }

        // Normalize browser names
        $data = $this->normalizeBrowserNames($rawData);

        // Handle displaying only top items and group the rest
        if ($data->count() > $this->limit) {
            $topItems = $data->take($this->limit);
            $otherItemsSum = $data->slice($this->limit)->sum('total');

            if ($otherItemsSum > 0) {
                $topItems->push((object) [
                    'browser' => 'Others',
                    'total' => $otherItemsSum,
                ]);
            }

            $data = $topItems;
        }

        // Use browser-specific colors instead of generated palette
        $colors = $this->getBrowserColors($data->pluck('browser')->toArray());

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => $colors,
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $data->pluck('browser')->toArray(),
        ];
    }

    /**
     * Normalize browser names to group similar browsers
     */
    protected function normalizeBrowserNames(Collection $data): Collection
    {
        $normalized = collect();
        $totals = [];

        foreach ($data as $item) {
            $browser = $item->browser;

            // Normalize common browser names
            if (preg_match('/chrome/i', $browser) && ! preg_match('/edge/i', $browser)) {
                $normalized_name = 'Chrome';
            } elseif (preg_match('/firefox/i', $browser)) {
                $normalized_name = 'Firefox';
            } elseif (preg_match('/safari/i', $browser) && ! preg_match('/chrome/i', $browser)) {
                $normalized_name = 'Safari';
            } elseif (preg_match('/edge/i', $browser)) {
                $normalized_name = 'Edge';
            } elseif (preg_match('/opera/i', $browser)) {
                $normalized_name = 'Opera';
            } elseif (preg_match('/samsung/i', $browser)) {
                $normalized_name = 'Samsung Internet';
            } elseif (preg_match('/IE|Trident|MSIE/i', $browser)) {
                $normalized_name = 'Internet Explorer';
            } else {
                $normalized_name = $browser;
            }

            // Add to totals array
            if (! isset($totals[$normalized_name])) {
                $totals[$normalized_name] = 0;
            }

            $totals[$normalized_name] += $item->total;
        }

        // Convert back to objects collection
        foreach ($totals as $browser => $total) {
            $normalized->push((object) [
                'browser' => $browser,
                'total' => $total,
            ]);
        }

        // Sort by total
        return $normalized->sortByDesc('total')->values();
    }

    /**
     * Get brand colors for common browsers
     */
    protected function getBrowserColors(array $browsers): array
    {
        // Standard brand colors for popular browsers
        $browserColorMap = [
            'Chrome' => '#4285F4',       // Google Blue
            'Firefox' => '#FF7139',      // Firefox Orange
            'Safari' => '#006CFF',       // Safari Blue
            'Edge' => '#0078D7',         // Edge Blue
            'Opera' => '#FF1B2D',        // Opera Red
            'Internet Explorer' => '#0076D6', // IE Blue
            'Samsung Internet' => '#1428A0', // Samsung Blue
            'Others' => '#808080',       // Grey for Others
        ];

        // Additional colors for fallback
        $fallbackColors = [
            '#3498db', // Blue
            '#2ecc71', // Green
            '#e74c3c', // Red
            '#f39c12', // Orange
            '#9b59b6', // Purple
            '#1abc9c', // Teal
            '#34495e', // Dark Blue
            '#d35400', // Dark Orange
        ];

        $colors = [];
        $fallbackIndex = 0;

        foreach ($browsers as $browser) {
            if (isset($browserColorMap[$browser])) {
                $colors[] = $browserColorMap[$browser];
            } else {
                // Use fallback colors for browsers not in the map
                $colors[] = $fallbackColors[$fallbackIndex % count($fallbackColors)];
                $fallbackIndex++;
            }
        }

        return $colors;
    }
}
