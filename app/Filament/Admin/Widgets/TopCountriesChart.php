<?php

namespace App\Filament\Admin\Widgets;

use App\Models\LinkVisit;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TopCountriesChart extends ChartWidget
{
    protected ?string $heading = 'Top Visitor Countries';

    protected ?string $pollingInterval = null;

    public ?string $filter = 'month';

    // Add filter state
    protected ?int $limit = 10;

    public static function canView(): bool
    {
        return auth()->user()->can('view link');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $query = LinkVisit::query();

        // Apply date filter
        $dateRange = $this->getDateRange();
        $query->whereBetween('created_at', [
            $dateRange['start'],
            $dateRange['end'],
        ]);

        // Get the raw country data
        $data = $query->selectRaw('count(*) as total, country')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('total')
            ->get();

        // If no data is available, return empty state
        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e2e8f0'],
                        'borderColor' => '#ffffff',
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['No data available'],
            ];
        }

        // Handle displaying only top items and group the rest
        if ($data->count() > $this->limit) {
            $topItems = $data->take($this->limit);
            $otherItemsSum = $data->slice($this->limit)->sum('total');

            if ($otherItemsSum > 0) {
                $topItems->push((object) [
                    'country' => 'Others',
                    'total' => $otherItemsSum,
                ]);
            }

            $data = $topItems;
        }

        // Generate colors for the countries
        $colors = $this->generateColorPalette(count($data));

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data->pluck('country')->toArray(),
        ];
    }

    protected function getDateRange(): array
    {
        return match ($this->filter) {
            'week' => [
                'start' => Carbon::now()->subDays(7),
                'end' => Carbon::now(),
            ],
            'month' => [
                'start' => Carbon::now()->subDays(30),
                'end' => Carbon::now(),
            ],
            'quarter' => [
                'start' => Carbon::now()->subDays(90),
                'end' => Carbon::now(),
            ],
            'year' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now(),
            ],
            'all' => [
                'start' => Carbon::now()->subYears(5),
                'end' => Carbon::now(),
            ],
            default => [
                'start' => Carbon::now()->subDays(30),
                'end' => Carbon::now(),
            ],
        };
    }

    protected function generateColorPalette(int $count): array
    {
        $colors = [
            '#3498db', // Blue
            '#2ecc71', // Green
            '#e74c3c', // Red
            '#f1c40f', // Yellow
            '#9b59b6', // Purple
            '#1abc9c', // Turquoise
            '#e67e22', // Orange
            '#34495e', // Navy
            '#7f8c8d', // Gray
            '#16a085', // Dark Turquoise
            '#d35400', // Dark Orange
            '#2c3e50', // Dark Navy
        ];

        // If we need more colors than we have, generate them
        while (count($colors) < $count) {
            $colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        }

        return array_slice($colors, 0, $count);
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
            'quarter' => 'Last 90 days',
            'year' => 'This year',
            'all' => 'All time',
        ];
    }
}
