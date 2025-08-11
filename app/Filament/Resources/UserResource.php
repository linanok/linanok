<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\History\HistoryAction;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * Filament Resource for managing application users.
 *
 * This resource provides a complete CRUD interface for users within the admin panel,
 * including user authentication details, role assignments, permission management,
 * and account status control. Users can be activated/deactivated and assigned
 * multiple roles and direct permissions.
 *
 * @see \App\Models\User
 * @see \Spatie\Permission\Traits\HasRoles
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->confirmed()
                    ->visible(fn (string $context): bool => $context === 'create'),

                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->visible(fn (string $context): bool => $context === 'create'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Inactive users cannot log in')
                    ->default(true),

                Toggle::make('is_super_admin')
                    ->label('Super Admin')
                    ->helperText('Super admins have full access to all features')
                    ->default(false),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->visible(fn ($livewire) => ! $livewire instanceof UsersRelationManager)
                    ->requiredWithout('permissions'),

                Select::make('permissions')
                    ->multiple()
                    ->relationship('permissions', 'name')
                    ->preload()
                    ->searchable()
                    ->requiredWithout('roles'),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn (?User $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn (?User $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Users')
                    ->trueLabel('Active Users')
                    ->falseLabel('Inactive Users')
                    ->boolean(),
            ])
            ->actions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

                HistoryAction::make(static::class),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->visible(fn () => request()->user()->can('delete user')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'history' => Pages\UserHistory::route('/{record}/history'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
