<?php

namespace App\Filament\Resources\Tags\Widgets;

use App\Models\LinkVisit;

class TagVisitsByCountryPieChart extends BaseTagVisitsPieChart
{
    protected ?string $chartHeading = 'Visitors By Country';

    protected ?int $limit = 10; // Show top 10 countries

    protected function getData(): array
    {
        $query = LinkVisit::query()->whereIn('link_id', $this->record->links()->select('links.id'));

        // Apply date filters
        $query = $this->applyDateFilter($query);

        // Get the raw country data
        $data = $query->selectRaw('count(*) as total, country')
            ->whereNotNull('country')
            ->groupBy('country')
            ->get()
            ->sortByDesc('total')
            ->values();

        // If no data is available, return empty state
        if ($data->isEmpty()) {
            return $this->getEmptyStateData();
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
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $data->pluck('country')->toArray(),
        ];
    }
}
