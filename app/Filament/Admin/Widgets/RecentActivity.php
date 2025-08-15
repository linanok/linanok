<?php

namespace App\Filament\Admin\Widgets;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

class RecentActivity extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->is_super_admin ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['causer', 'subject'])
                    ->orderBy('id', 'desc')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('subject')
                    ->label('Activity')
                    ->icon(fn ($record): string => match ($record->event) {
                        'created' => 'heroicon-m-plus',
                        'updated' => 'heroicon-m-pencil',
                        'deleted' => 'heroicon-m-trash',
                        default => 'heroicon-m-clock',
                    })
                    ->iconColor(fn ($record): string => match ($record->event) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'info',
                    })
                    ->description(fn ($record) => new HtmlString('<span class="text-xs text-gray-500">'.class_basename($record->subject_type).'</span>'))
                    ->searchable(false)
                    ->sortable(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
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
                    ]),
            ])
            ->paginated(false)
            ->heading(null)  // Remove the heading completely
            ->striped(false) // Remove striped rows for a cleaner look
            ->contentFooter(null); // Remove the footer
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
