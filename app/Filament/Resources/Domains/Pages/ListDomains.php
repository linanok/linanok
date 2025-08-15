<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Admin\Widgets\AddCurrentDomain;
use App\Filament\Resources\Domains\DomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

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
