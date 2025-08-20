<?php

namespace App\Filament\Resources\Tags\RelationManagers;

use App\Filament\Resources\Links\LinkResource;
use App\Models\Link;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    public function form(Schema $schema): Schema
    {
        return LinkResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return LinkResource::table($table)
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->recordTitle(fn (Link $record): string => "$record->short_path | $record->original_url")
                    ->recordSelectSearchColumns([
                        'short_path', 'original_url', 'slug', 'description',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make()
                    ->fetchSelectedRecords(false),
            ]);
    }
}
