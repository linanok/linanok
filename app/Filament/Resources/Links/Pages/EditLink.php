<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use App\Filament\Resources\Links\Widgets\VisitsByBrowserPieChart;
use App\Filament\Resources\Links\Widgets\VisitsByCountryPieChart;
use App\Filament\Resources\Links\Widgets\VisitsByPlatformPieChart;
use App\Filament\Resources\Links\Widgets\VisitsCountChart;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisitsCountChart::class,
            VisitsByBrowserPieChart::class,
            VisitsByPlatformPieChart::class,
            VisitsByCountryPieChart::class,
        ];
    }
}
