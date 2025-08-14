<?php

namespace App\Filament\Resources\Roles\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Schema $schema): Schema
    {
        return UserResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return UserResource::table($table)
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->recordTitle(fn (User $record): string => $record->name)
                    ->recordSelectSearchColumns(['name', 'email']),
            ])
            ->recordActions([
                // ...
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                // ...
                DetachBulkAction::make(),
            ]);
    }
}
