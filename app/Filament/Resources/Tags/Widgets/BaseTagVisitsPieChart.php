<?php

namespace App\Filament\Resources\Tags\Widgets;

use Carbon\Carbon;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class BaseTagVisitsPieChart extends ChartWidget
{
    use InteractsWithRecord;

    protected ?string $pollingInterval = null;

    // Add filter state
    public ?string $filter = 'month';

    // Define these in child classes
    protected ?string $chartHeading = null;

    public function __construct()
    {
        // Set the default heading if not specified
        if (! $this->heading && $this->chartHeading) {
            $this->heading = $this->chartHeading;
        }
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
            'quarter' => 'Last 90 days',
            'year' => 'This year',
            'all' => 'All time',
        ];
    }

    protected function generateColorPalette(int $count): array
    {
        // Base colors for the palette (vibrant, distinct colors)
        $baseColors = [
            '#3498db', // Blue
            '#2ecc71', // Green
            '#e74c3c', // Red
            '#f39c12', // Orange
            '#9b59b6', // Purple
            '#1abc9c', // Teal
            '#34495e', // Dark Blue
            '#d35400', // Dark Orange
            '#c0392b', // Dark Red
            '#16a085', // Dark Teal
            '#8e44ad', // Dark Purple
            '#27ae60', // Dark Green
            '#f1c40f', // Yellow
            '#e67e22', // Light Orange
            '#3498db', // Light Blue
        ];

        // If we need more colors than in our base palette, generate them
        if ($count > count($baseColors)) {
            // Add more colors by adjusting brightness of base colors
            for ($i = 0; $i < $count - count($baseColors); $i++) {
                $baseIndex = $i % count($baseColors);
                $baseColor = $baseColors[$baseIndex];

                // Modify the base color slightly
                $hex = substr($baseColor, 1);
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));

                // Lighten the color
                $factor = 0.85 + ($i / count($baseColors) * 0.15);
                $r = min(255, $r * $factor);
                $g = min(255, $g * $factor);
                $b = min(255, $b * $factor);

                $baseColors[] = sprintf('#%02x%02x%02x', $r, $g, $b);
            }
        }

        return array_slice($baseColors, 0, $count);
    }

    protected function applyDateFilter(Builder $query): Builder
    {
        return match ($this->filter) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week' => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
            'month' => $query->where('created_at', '>=', Carbon::now()->subDays(30)),
            'quarter' => $query->where('created_at', '>=', Carbon::now()->subDays(90)),
            'year' => $query->whereYear('created_at', Carbon::now()->year),
            'all' => $query,
            default => $query->where('created_at', '>=', Carbon::now()->subDays(30)),
        };
    }

    protected function getEmptyStateData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [1],
                    'backgroundColor' => ['#e2e8f0'],
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => ['No data available'],
        ];
    }
}
