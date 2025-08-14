<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use App\Filament\Resources\Links\Widgets\LinkVisitsByBrowserPieChart;
use App\Filament\Resources\Links\Widgets\LinkVisitsByCountryPieChart;
use App\Filament\Resources\Links\Widgets\LinkVisitsByPlatformPieChart;
use App\Filament\Resources\Links\Widgets\LinkVisitsCountChart;
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
            LinkVisitsCountChart::class,
            LinkVisitsByBrowserPieChart::class,
            LinkVisitsByPlatformPieChart::class,
            LinkVisitsByCountryPieChart::class,
        ];
    }
}
