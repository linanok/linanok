<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ImportAction::make()
                ->importer(UserImporter::class)
                ->authorize(function () {
                    return auth()->user()->canAny(['create user', 'update user']);
                }),

            ExportAction::make()
                ->exporter(UserExporter::class)
                ->authorize(function () {
                    return auth()->user()->can('view user');
                }),
        ];
    }
}
