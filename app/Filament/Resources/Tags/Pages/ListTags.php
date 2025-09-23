<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Exports\TagExporter;
use App\Filament\Resources\Tags\TagResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ExportAction::make()
                ->exporter(TagExporter::class)
                ->authorize(function () {
                    return auth()->user()->can('view tag');
                }),
        ];
    }
}
