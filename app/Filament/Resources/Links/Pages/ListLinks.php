<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Admin\Widgets\AddCurrentDomain;
use App\Filament\Resources\Links\LinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AddCurrentDomain::class,
        ];
    }
}
