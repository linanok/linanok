<?php

namespace App\Filament\Resources\Links;

use App\Filament\Resources\Domains\RelationManagers\LinksRelationManager;
use App\Filament\Resources\Links\Filters\QueryBuilder\Constraints\HasPasswordConstraint;
use App\Filament\Resources\Links\Pages\CreateLink;
use App\Filament\Resources\Links\Pages\EditLink;
use App\Filament\Resources\Links\Pages\LinkHistory;
use App\Filament\Resources\Links\Pages\ListLinks;
use App\Filament\Resources\Links\Widgets\VisitsByBrowserPieChart;
use App\Filament\Resources\Links\Widgets\VisitsByCountryPieChart;
use App\Filament\Resources\Links\Widgets\VisitsByPlatformPieChart;
use App\Filament\Resources\Links\Widgets\VisitsCountChart;
use App\History\HistoryAction;
use App\Models\Domain;
use App\Models\Link;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Table;

/**
 * Filament Resource for managing shortened links.
 *
 * This is the core resource of the URL shortener application, providing a comprehensive
 * interface for creating, editing, and managing shortened links. The resource includes:
 * - Rich form with tabbed interface for link configuration
 * - Advanced filtering and search capabilities
 * - Analytics widgets for visit tracking
 * - Domain and tag relationship management
 * - Access control features (passwords, availability windows)
 *
 * @see \App\Models\Link
 * @see \App\Services\VisitService
 */
class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $slug = 'links';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Link Management';

    protected static ?string $recordTitleAttribute = 'short_path';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Link')
                    ->tabs([
                        Tab::make('Basic Information')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('original_url')
                                            ->label('Original URL')
                                            ->required()
                                            ->url()
                                            ->columnSpan('full')
                                            ->placeholder('https://example.com')
                                            ->maxLength(2048)
                                            ->suffixAction(
                                                Action::make('visit')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->tooltip('Visit original URL')
                                                    ->url(fn ($record) => $record->original_url, true)
                                                    ->visible(fn ($record) => $record !== null)
                                            )
                                            ->helperText('Enter the URL you want to shorten'),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('slug')
                                                    ->autocomplete(false)
                                                    ->placeholder(fn ($record) => $record === null ? 'custom-slug' : '-')
                                                    ->helperText('Leave empty for auto-generation')
                                                    ->readOnly(fn ($record) => $record !== null)
                                                    ->maxLength(50),

                                                TextInput::make('short_path')
                                                    ->suffixActions([
                                                        Action::make('copy')
                                                            ->icon('heroicon-m-clipboard')
                                                            ->tooltip('Copy to clipboard')
                                                            ->disabled(fn (Link $record) => ! $record->is_available)
                                                            ->extraAttributes(fn ($record) => [
                                                                'onclick' => "navigator.clipboard.writeText('".get_short_url($record)."')",
                                                            ])
                                                            ->action(fn () => Notification::make('link_copied_to_clipboard')
                                                                ->title('URL copied to clipboard')
                                                                ->success()
                                                                ->send()
                                                            ),

                                                        Action::make('visit')
                                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                                            ->tooltip('Visit original URL')
                                                            ->url(fn ($livewire, Link $record) => LinkResource::getShortUrl($record, $livewire), true)
                                                            ->disabled(fn (Link $record) => ! $record->is_available)
                                                            ->visible(fn ($record) => $record !== null),
                                                    ])
                                                    ->visible(fn ($record) => $record !== null)
                                                    ->readOnly(),
                                            ]),

                                        Flex::make([
                                            TextEntry::make('visit_count')
                                                ->icon('heroicon-c-eye')
                                                ->numeric(),

                                            TextEntry::make('is_available')
                                                ->label('Status')
                                                ->state(fn ($record) => $record->is_available ? 'Available' : 'Unavailable')
                                                ->icon(fn ($record) => $record->is_available ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                                ->iconColor(fn ($record) => $record->is_available ? 'success' : 'danger'),
                                        ])->visible(fn ($record) => $record !== null),
                                    ])
                                    ->columns(1),
                            ]),

                        Tab::make('Access Control')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('password')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password')
                                            ->helperText('Optional: Protect the link with a password'),

                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->helperText('Enable or disable this link')
                                            ->default(true),

                                        Grid::make()
                                            ->schema([
                                                DateTimePicker::make('available_at')
                                                    ->label('Available From')
                                                    ->placeholder('Select date/time')
                                                    ->seconds(false)
                                                    ->helperText('Optional: Set when this link becomes available'),

                                                DateTimePicker::make('unavailable_at')
                                                    ->label('Available Until')
                                                    ->placeholder('Select date/time')
                                                    ->seconds(false)
                                                    ->after('available_at')
                                                    ->helperText('Optional: Set when this link expires'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Advanced Options')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('forward_query_parameters')
                                                    ->label('Forward Query Parameters')
                                                    ->helperText('Pass original URL query parameters to the destination'),

                                                Toggle::make('send_ref_query_parameter')
                                                    ->label('Send Referrer')
                                                    ->helperText('Add ref parameter to track traffic source'),
                                            ]),

                                        Select::make('domains')
                                            ->relationship('domains', 'host')
                                            ->multiple()
                                            ->preload()
                                            ->required()
                                            ->searchable()
                                            ->helperText('Choose which domains can host this shortened link'),

                                        Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->helperText('Add tags to categorize and organize this link'),
                                    ]),
                            ]),

                        Tab::make('Description')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        RichEditor::make('description')
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'codeBlock',
                                                'h2',
                                                'h3',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpan('full')
                                            ->helperText('Optional description to help identify and organize this link'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('short_path')
                    ->label('Short URL')
                    ->url(fn ($record, $livewire) => LinkResource::getShortUrl($record, $livewire))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition('after'),

                TextColumn::make('original_url')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->original_url;
                    })
                    ->searchable(),

                // @phpstan-ignore-next-line method.notFound
                TextColumn::make('visit_count')
                    ->label('Visits')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->numericAbbreviate()
                    ->tooltip(fn ($record) => number_format($record->visit_count)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean(),

                IconColumn::make('has_password')
                    ->label('Password Protected')
                    ->boolean()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('links.id', 'desc')
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        HasPasswordConstraint::make('has_password'),

                        NumberConstraint::make('visit_count')
                            ->integer(),

                        DateConstraint::make('created_at'),

                        DateConstraint::make('updated_at'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

                HistoryAction::make(static::class),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->authorize('delete link'),
            ]);
    }

    public static function getShortUrl(mixed $record, mixed $livewire): ?string
    {
        if ($livewire instanceof LinksRelationManager) {
            $domain = $livewire->ownerRecord instanceof Domain ? $livewire->ownerRecord : null;
        } else {
            $domain = null;
        }

        return get_short_url($record, $domain);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLinks::route('/'),
            'create' => CreateLink::route('/create'),
            'edit' => EditLink::route('/{record}/edit'),
            'history' => LinkHistory::route('/{record}/history'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'original_url', 'description'];
    }

    public static function getWidgets(): array
    {
        return [
            VisitsCountChart::class,
            VisitsByBrowserPieChart::class,
            VisitsByPlatformPieChart::class,
            VisitsByCountryPieChart::class,
        ];
    }
}
