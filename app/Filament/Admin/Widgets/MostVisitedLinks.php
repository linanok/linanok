<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Resources\Domains\RelationManagers\LinksRelationManager;
use App\Filament\Resources\Links\LinkResource;
use App\Models\Link;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MostVisitedLinks extends BaseWidget
{
    protected static ?string $heading = 'Most Visited Links';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('view link');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Link::query()
                    ->orderBy('visit_count', 'desc')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('short_path')
                    ->label('Short URL')
                    ->url(function ($record, $livewire) {
                        if ($livewire instanceof LinksRelationManager) {
                            $domain = $livewire->ownerRecord;
                        } else {
                            $domain = null;
                        }

                        return get_short_url($record, $domain);
                    })
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition('after'),

                TextColumn::make('original_url')
                    ->label('Original URL')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->original_url;
                    }),

                IconColumn::make('has_password')
                    ->label('Protected')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),

                TextColumn::make('visit_count')
                    ->label('Visits')
                    ->alignRight()
                    ->badge()
                    ->color('success')
                    ->numericAbbreviate()
                    ->tooltip(fn ($record) => number_format($record->visit_count)),
            ])
            ->recordActions([
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

                ViewAction::make()
                    ->schema(fn (Schema $schema): Schema => LinkResource::form($schema))
                    ->visible(fn ($record) => auth()->user()->can('view', $record) && ! auth()->user()->can('update', $record)),

                EditAction::make()
                    ->schema(fn (Schema $schema): Schema => LinkResource::form($schema))
                    ->visible(fn ($record) => auth()->user()->can('update', $record)),
            ])
            ->paginated(false)
            ->striped();
    }
}
