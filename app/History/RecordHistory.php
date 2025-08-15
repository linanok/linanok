<?php

namespace App\History;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\HtmlString;

class RecordHistory extends ManageRelatedRecords
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'History';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock-rewind';

    public static function getNavigationLabel(): string
    {
        return 'Activity Log';
    }

    public function getRelationship(): Relation|Builder
    {
        return $this->getOwnerRecord()
            ->activities()
            ->with('causer')  // Eager load the causer relationship
            ->latest('id');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Placeholder::make('event')
                            ->content(fn ($record): string => ucfirst($record->event))
                            ->columnSpanFull(),

                        Placeholder::make('causer')
                            ->content(fn ($record): ?string => $record->causer?->name ?? 'System')
                            ->columnSpanFull(),

                        Placeholder::make('timestamp')
                            ->content(fn ($record): string => $record->created_at->format('F j, Y \a\t g:i:s A'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Changes')
                    ->description(fn ($record): ?string => $this->getChangesSummaryText($record))
                    ->schema([
                        KeyValue::make('properties.old')
                            ->label('Previous Values')
                            ->visible(fn ($record): bool => ! empty($record->changes['old']))
                            ->columnSpanFull(),

                        KeyValue::make('properties.attributes')
                            ->label('New Values')
                            ->visible(fn ($record): bool => ! empty($record->changes['attributes']))
                            ->columnSpanFull(),

                        Placeholder::make('no_changes')
                            ->content('No fields were modified in this action')
                            ->visible(fn ($record): bool => empty($record->changes['old']) &&
                                empty($record->changes['attributes'])
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getChangesSummaryText($record): ?string
    {
        if ($record->event === 'created') {
            return 'Record was created';
        }

        if ($record->event === 'deleted') {
            return 'Record was deleted';
        }

        if (empty($record->changes['old']) && empty($record->changes['attributes'])) {
            return 'No changes were made';
        }

        $changes = collect($record->changes['attributes'] ?? [])
            ->map(function ($newValue, $field) use ($record) {
                $oldValue = $record->changes['old'][$field] ?? null;

                return "{$field}: {$oldValue} → {$newValue}";
            })
            ->join(', ');

        return $changes;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'info',
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('causer.name')
                    ->label('Modified By')
                    ->default('System')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Summary')
                    ->html()
                    ->formatStateUsing(fn ($record): HtmlString => $this->getChangesSummary($record))
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('activity_log.id', 'desc')
            ->striped()
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Activity Details'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    protected function getChangesSummary($record): HtmlString
    {
        if ($record->event === 'created') {
            return new HtmlString('<span class="text-success-600">Record was created</span>');
        }

        if ($record->event === 'deleted') {
            return new HtmlString('<span class="text-danger-600">Record was deleted</span>');
        }

        if (empty($record->changes['old']) && empty($record->changes['attributes'])) {
            return new HtmlString('<span class="text-gray-500">No changes were made</span>');
        }

        $changes = collect($record->changes['attributes'] ?? [])
            ->map(function ($newValue, $field) use ($record) {
                $oldValue = $record->changes['old'][$field] ?? null;

                return "<strong>{$field}</strong>: {$oldValue} → {$newValue}";
            })
            ->join('<br>');

        return new HtmlString($changes);
    }
}
