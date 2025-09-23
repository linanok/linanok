<?php

namespace App\Filament\Exports;

use App\Models\Domain;
use App\Models\Link;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class LinkExporter extends Exporter
{
    protected static ?string $model = Link::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),

            ExportColumn::make('is_active')
                ->enabledByDefault(false),

            ExportColumn::make('is_available'),

            ExportColumn::make('slug'),

            ExportColumn::make('original_url'),

            ExportColumn::make('short_path')
                ->prefix('/l/')
                ->enabledByDefault(false),

            ExportColumn::make('short_url')
                ->state(fn (Link $record, array $options) => get_short_url($record, Domain::find($options['domain']))),

            ExportColumn::make('tags.name'),

            ExportColumn::make('available_at')
                ->enabledByDefault(false),

            ExportColumn::make('unavailable_at')
                ->enabledByDefault(false),

            ExportColumn::make('forward_query_parameters')
                ->enabledByDefault(false),

            ExportColumn::make('visit_count')
                ->enabledByDefault(false),

            ExportColumn::make('send_ref_query_parameter')
                ->enabledByDefault(false),

            ExportColumn::make('created_at'),

            ExportColumn::make('updated_at')
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your link export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('domain')
                ->label('Default Short URL Domain')
                ->options(
                    Domain::orderBy('host')
                        ->pluck('host', 'id')
                )
                ->visible(fn () => auth()->user()->can('view domain')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return parent::modifyQuery($query)->orderBy('id');
    }
}
