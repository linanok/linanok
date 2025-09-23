<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),

            ExportColumn::make('is_active')
                ->boolean()
                ->enabledByDefault(false),

            ExportColumn::make('name'),

            ExportColumn::make('email'),

            ExportColumn::make('is_super_admin'),

            ExportColumn::make('roles.name'),

            ExportColumn::make('permissions.name'),

            ExportColumn::make('created_at'),

            ExportColumn::make('updated_at')
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your user export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return parent::modifyQuery($query)->orderBy('id');
    }
}
