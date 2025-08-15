<?php

namespace App\Filament\Resources\Links\RelationManagers;

use App\Filament\Resources\Domains\DomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public function form(Schema $schema): Schema
    {
        return DomainResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return DomainResource::table($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
