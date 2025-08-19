<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Tags\Widgets\TagVisitsByBrowserPieChart;
use App\Filament\Resources\Tags\Widgets\TagVisitsByCountryPieChart;
use App\Filament\Resources\Tags\Widgets\TagVisitsByPlatformPieChart;
use App\Filament\Resources\Tags\Widgets\TagVisitsCountChart;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TagVisitsCountChart::class,
            TagVisitsByBrowserPieChart::class,
            TagVisitsByPlatformPieChart::class,
            TagVisitsByCountryPieChart::class,
        ];
    }
}
