<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Admin\Widgets\AddCurrentDomain;
use App\Filament\Imports\LinkImporter;
use App\Filament\Resources\Links\LinkResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ImportAction::make()
                ->importer(LinkImporter::class)
                ->authorize(function () {
                    return auth()->user()->canAny(['create link', 'update link']);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AddCurrentDomain::class,
        ];
    }
}
