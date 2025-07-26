<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\RelationManagers\LinksRelationManager;
use App\Filament\Resources\LinkResource\Filters\QueryBuilder\Constraints\HasPasswordConstraint;
use App\Filament\Resources\LinkResource\Pages;
use App\Filament\Resources\LinkResource\Widgets\LinkVisitsByBrowserPieChart;
use App\Filament\Resources\LinkResource\Widgets\LinkVisitsByCountryPieChart;
use App\Filament\Resources\LinkResource\Widgets\LinkVisitsByPlatformPieChart;
use App\Filament\Resources\LinkResource\Widgets\LinkVisitsCountChart;
use App\History\HistoryAction;
use App\Models\Link;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
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
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
 * @see \App\Services\LinkVisitService
 */
class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $slug = 'links';

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Link Management';

    protected static ?string $recordTitleAttribute = 'short_path';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Link')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
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
                                                    ->unique(ignorable: fn ($record) => $record)
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
                                                                'data-copy-url' => get_short_url($record),
                                                                'data-tooltip-message' => 'URL copied to clipboard',
                                                                'x-on:click' => 'navigator.clipboard.writeText($el.dataset.copyUrl); $tooltip($el.dataset.tooltipMessage);',
                                                            ]),

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

                                        Placeholder::make('visit_count_display')
                                            ->label('Statistics')
                                            ->content(function ($record) {
                                                return new HtmlString(
                                                    "<div class='flex space-x-4'>"
                                                    ."<div class='flex items-center'>"
                                                    ."<div class='p-2 bg-primary-100 dark:bg-primary-800 rounded-full mr-2'>"
                                                    .svg('heroicon-c-eye', 'w-5 h-5 text-primary-600 dark:text-primary-300')->toHtml()
                                                    .'</div>'
                                                    .'<div>'
                                                    ."<span class='text-sm font-medium'>".number_format($record->visit_count).'</span>'
                                                    ."<p class='text-xs text-gray-500 dark:text-gray-400'>Visits</p>"
                                                    .'</div>'
                                                    .'</div>'
                                                    ."<div class='flex items-center'>"
                                                    ."<div class='p-2 bg-".($record->is_available ? 'success' : 'danger').'-100 dark:bg-'.($record->is_available ? 'success' : 'danger')."-800 rounded-full mr-2'>"
                                                    .(
                                                        $record->is_available ?
                                                            svg('heroicon-o-check-circle', 'w-5 h-5 text-success-600 dark:text-success-300')->toHtml() :
                                                            svg('heroicon-o-x-circle', 'w-5 h-5 text-danger-600 dark:text-danger-300')->toHtml()
                                                    )
                                                    .'</div>'
                                                    .'<div>'
                                                    ."<span class='text-sm font-medium'>".($record->is_available ? 'Available' : 'Unavailable').'</span>'
                                                    ."<p class='text-xs text-gray-500 dark:text-gray-400'>Status</p>"
                                                    .'</div>'
                                                    .'</div>'
                                                    .'</div>'
                                                );
                                            })
                                            ->visible(fn ($record) => $record !== null),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('Access Control')
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
                                                    ->seconds(false),

                                                DateTimePicker::make('unavailable_at')
                                                    ->label('Available Until')
                                                    ->placeholder('Select date/time')
                                                    ->seconds(false)
                                                    ->after('available_at'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Advanced Options')
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
                                            ->searchable(),

                                        Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Description')
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
                                            ->columnSpan('full'),
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

                TextColumn::make('visit_count')
                    ->label('Visits')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

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
            ->actions([
                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

                HistoryAction::make(static::class),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->visible(fn () => request()->user()->can('delete link')),
            ]);
    }

    public static function getShortUrl($record, $livewire)
    {
        if ($livewire instanceof LinksRelationManager) {
            $domain = $livewire->ownerRecord;
        } else {
            $domain = null;
        }

        return get_short_url($record, $domain);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinks::route('/'),
            'create' => Pages\CreateLink::route('/create'),
            'edit' => Pages\EditLink::route('/{record}/edit'),
            'history' => Pages\LinkHistory::route('/{record}/history'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'original_url', 'description'];
    }

    public static function getWidgets(): array
    {
        return [
            LinkVisitsCountChart::class,
            LinkVisitsByBrowserPieChart::class,
            LinkVisitsByPlatformPieChart::class,
            LinkVisitsByCountryPieChart::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('visits');
    }
}
