<?php

namespace App\Filament\Resources\Tags\Widgets;

use App\Models\Tag;
use App\Models\Visit;
use Illuminate\Support\Collection;

class TagVisitsByPlatformPieChart extends BaseTagVisitsPieChart
{
    protected ?string $chartHeading = 'Visitors By Platform';

    protected ?int $limit = 8; // Show top 8 platforms

    /**
     * Override getData to implement platform name normalization
     */
    protected function getData(): array
    {
        $query = Visit::query()->whereIn('link_id', $this->record instanceof Tag ? $this->record->links()->select('links.id') : []);

        // Apply date filters
        $query = $this->applyDateFilter($query);

        // Get the raw platform data
        $rawData = $query->selectRaw('count(*) as total, platform')
            ->whereNotNull('platform')
            ->groupBy('platform')
            ->get();

        // If no data is available, return empty state
        if ($rawData->isEmpty()) {
            return $this->getEmptyStateData();
        }

        // Normalize platform names
        $data = $this->normalizePlatformNames($rawData);

        // Handle displaying only top items and group the rest
        if ($data->count() > $this->limit) {
            $topItems = $data->take($this->limit);
            $otherItemsSum = $data->slice($this->limit)->sum('total');

            if ($otherItemsSum > 0) {
                $topItems->push((object) [
                    'platform' => 'Others',
                    'total' => $otherItemsSum,
                ]);
            }

            $data = $topItems;
        }

        // Use platform-specific colors
        $colors = $this->getPlatformColors($data->pluck('platform')->toArray());

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => $colors,
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $data->pluck('platform')->toArray(),
        ];
    }

    /**
     * Normalize platform names to group similar platforms
     */
    protected function normalizePlatformNames(Collection $data): Collection
    {
        $normalized = collect();
        $totals = [];

        foreach ($data as $item) {
            $platform = $item->platform;

            // Normalize common platform names
            if (preg_match('/windows/i', $platform)) {
                $normalized_name = 'Windows';
            } elseif (preg_match('/mac/i', $platform) || preg_match('/darwin/i', $platform)) {
                $normalized_name = 'macOS';
            } elseif (preg_match('/ios/i', $platform) || preg_match('/iphone|ipad/i', $platform)) {
                $normalized_name = 'iOS';
            } elseif (preg_match('/android/i', $platform)) {
                $normalized_name = 'Android';
            } elseif (preg_match('/linux/i', $platform)) {
                $normalized_name = 'Linux';
            } elseif (preg_match('/chrome\s?os/i', $platform)) {
                $normalized_name = 'Chrome OS';
            } else {
                $normalized_name = $platform;
            }

            // Add to totals array
            if (! isset($totals[$normalized_name])) {
                $totals[$normalized_name] = 0;
            }

            $totals[$normalized_name] += $item->total;
        }

        // Convert back to objects collection
        foreach ($totals as $platform => $total) {
            $normalized->push((object) [
                'platform' => $platform,
                'total' => $total,
            ]);
        }

        // Sort by total
        return $normalized->sortByDesc('total')->values();
    }

    /**
     * Get brand colors for common platforms
     */
    protected function getPlatformColors(array $platforms): array
    {
        // Standard brand colors for popular platforms
        $platformColorMap = [
            'Windows' => '#0078D7',    // Windows Blue
            'macOS' => '#999999',      // Apple Gray
            'iOS' => '#007AFF',        // iOS Blue
            'Android' => '#3DDC84',    // Android Green
            'Linux' => '#FCC624',      // Linux Yellow/Gold
            'Chrome OS' => '#4285F4',  // Google Blue
            'Others' => '#808080',     // Grey for Others
        ];

        // Additional colors for fallback
        $fallbackColors = $this->generateColorPalette(8);

        $colors = [];
        $fallbackIndex = 0;

        foreach ($platforms as $platform) {
            if (isset($platformColorMap[$platform])) {
                $colors[] = $platformColorMap[$platform];
            } else {
                // Use fallback colors for platforms not in the map
                $colors[] = $fallbackColors[$fallbackIndex % count($fallbackColors)];
                $fallbackIndex++;
            }
        }

        return $colors;
    }
}
