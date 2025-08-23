<?php

namespace App\Filament\Resources\Domains;

use App\Enums\Protocol;
use App\Filament\Resources\Domains\Pages\CreateCurrentDomain;
use App\Filament\Resources\Domains\Pages\CreateDomain;
use App\Filament\Resources\Domains\Pages\DomainHistory;
use App\Filament\Resources\Domains\Pages\EditDomain;
use App\Filament\Resources\Domains\Pages\ListDomains;
use App\Filament\Resources\Domains\RelationManagers\LinksRelationManager;
use App\History\HistoryAction;
use App\Models\Domain;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Filament Resource for managing domains.
 *
 * This resource provides a complete CRUD interface for domains within the admin panel.
 * Domains are used to host shortened links and can optionally host the admin panel itself.
 * The resource includes validation to ensure at least one domain remains available for
 * admin panel access.
 *
 * @see \App\Models\Domain
 * @see \App\Enums\Protocol
 */
class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $slug = 'domains';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Link Management';

    protected static ?string $recordTitleAttribute = 'host';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Radio::make('protocol')
                    ->options(Protocol::class)
                    ->default(Protocol::HTTPS)
                    ->required(),

                TextInput::make('host')
                    ->required()
                    ->placeholder('example.com:8000, localhost:8000, or 192.168.1.100:8000')
                    ->helperText('Domain name, localhost, or IP address with optional port number')
                    ->regex('/^localhost(:\d+)?$|^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)+([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])(:\d+)?$|^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d+)?$/')
                    ->validationAttribute('host')
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->default(true),

                Toggle::make('is_admin_panel_active')
                    ->default(false),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn (?Domain $record): string => $record?->created_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('protocol'),

                TextColumn::make('host')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->boolean(),

                IconColumn::make('is_admin_panel_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

                HistoryAction::make(static::class),
            ])->toolbarActions([
                DeleteBulkAction::make()->authorize('delete domain'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomains::route('/'),
            'create' => CreateDomain::route('/create'),
            'create-current' => CreateCurrentDomain::route('/create-current'),
            'edit' => EditDomain::route('/{record}/edit'),
            'history' => DomainHistory::route('/{record}/history'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            LinksRelationManager::class,
        ];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }
}
