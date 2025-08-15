<?php

namespace App\Filament\Resources\Links\Filters\QueryBuilder\Constraints;

use App\Filament\Resources\Links\Filters\QueryBuilder\Constraints\HasPasswordConstraint\Operators\IsTrueOperator;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Filters\QueryBuilder\Constraints\Concerns\CanBeNullable;
use Filament\Tables\Filters\QueryBuilder\Constraints\Constraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\Operators\IsFilledOperator;

class HasPasswordConstraint extends Constraint
{
    use CanBeNullable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(FilamentIcon::resolve('tables::filters.query-builder.constraints.boolean') ?? 'heroicon-m-check-circle');

        $this->operators([
            IsTrueOperator::class,
            IsFilledOperator::make()
                ->visible(fn (): bool => $this->isNullable()),
        ]);
    }
}
