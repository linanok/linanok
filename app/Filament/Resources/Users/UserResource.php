<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Roles\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\UserHistory;
use App\History\HistoryAction;
use App\Models\User;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->default(false)
                    ->live(),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->visible(fn ($livewire) => ! $livewire instanceof UsersRelationManager)
                    ->live()
                    ->visible(fn (Get $get) => ! $get('is_super_admin')),

                Select::make('permissions')
                    ->multiple()
                    ->relationship('permissions', 'name')
                    ->preload()
                    ->searchable()
                    ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        $isSuperAdmin = (bool) $get('is_super_admin');
                        $roles = $get('roles') ?? [];

                        if (! $isSuperAdmin && empty($roles) && empty($value)) {
                            $fail('Either the user must be a Super Admin, have at least one role, or have at least one permission.');
                        }
                    })
                    ->visible(fn (Get $get) => ! $get('is_super_admin')),

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
            ->recordActions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

                HistoryAction::make(static::class),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->authorize('delete user'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'history' => UserHistory::route('/{record}/history'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
