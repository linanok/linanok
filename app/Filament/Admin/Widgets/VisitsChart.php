<?php

namespace App\Filament\Admin\Widgets;

use App\Models\LinkVisit;
use App\Traits\DatabaseCompatible;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class VisitsChart extends ChartWidget
{
    use DatabaseCompatible;

    protected ?string $heading = 'Visits Timeline';

    protected ?string $pollingInterval = null;

    public ?string $filter = 'month';

    // Add filter state

    public static function canView(): bool
    {
        return auth()->user()->can('view link');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.3, // Slightly curved line
                    'borderWidth' => 2,
                ],
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getData(): array
    {
        // Get date range based on filter
        $dateRange = $this->getDateRange();
        $interval = $this->getInterval();

        $data = Trend::query(LinkVisit::query())
            ->between(
                start: $dateRange['start'],
                end: $dateRange['end'],
            )
            ->interval($interval)
            ->count();

        // Get unique visitors data using database-compatible date truncation
        $dateTruncSql = $this->getDateTruncSql($interval, 'created_at');
        $uniqueVisitors = Trend::query(
            LinkVisit::query()
                ->select('datetime')
                ->selectRaw('count(*) as aggregate')
                ->fromSub(
                    LinkVisit::query()
                        ->selectRaw("$dateTruncSql as datetime")
                        ->groupBy('ip', 'datetime'),
                    'sub')
                ->groupBy('datetime')
        )
            ->dateColumn('datetime')
            ->between(
                start: $dateRange['start'],
                end: $dateRange['end'],
            )
            ->interval($interval)
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total Visits',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3498db',
                    'backgroundColor' => 'rgba(52, 152, 219, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Unique Visitors',
                    'data' => $uniqueVisitors->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#2ecc71',
                    'backgroundColor' => 'rgba(46, 204, 113, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $this->formatLabel($value->date, $interval)),
        ];
    }

    protected function getDateRange(): array
    {
        return match ($this->filter) {
            'today' => [
                'start' => Carbon::today(),
                'end' => Carbon::today()->endOfDay(),
            ],
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
                'end' => Carbon::now()->endOfYear(),
            ],
            'all' => [
                'start' => Carbon::now()->subYears(5), // Show up to 5 years back
                'end' => Carbon::now(),
            ],
            default => [
                'start' => Carbon::now()->subDays(30),
                'end' => Carbon::now(),
            ],
        };
    }

    protected function getInterval(): string
    {
        return match ($this->filter) {
            'today' => 'hour',
            'week' => 'day',
            'month' => 'day',
            'quarter' => 'day',
            'year' => 'month',
            'all' => 'month',
            default => 'day',
        };
    }

    protected function formatLabel(string $date, string $interval): string
    {
        $carbon = Carbon::parse($date);

        return match ($interval) {
            'hour' => $carbon->format('h:i A'),
            'day' => $carbon->format('M j'),
            'week' => $carbon->format('M j'),
            'month' => $carbon->format('M Y'),
            'year' => $carbon->format('Y'),
            default => $carbon->format('M j'),
        };
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
}
