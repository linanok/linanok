<?php

namespace App\History;

use Filament\Actions\Action;

/**
 * History Action Helper
 *
 * Provides a standardized way to create history actions for Filament resources.
 * This creates a table action that links to the history page for a given record,
 * allowing users to view the activity log for that record.
 *
 * @see \App\History\RecordHistory
 * @see \Filament\Tables\Actions\Action
 */
class HistoryAction
{
    /**
     * Create a history action for a Filament resource.
     *
     * Creates a table action with a clock icon that navigates to the
     * history page for the selected record.
     *
     * @param  string  $resource  The fully qualified class name of the Filament resource
     * @return Action The configured history action
     */
    public static function make(string $resource): Action
    {
        return Action::make('history')
            ->icon('heroicon-o-clock')
            ->url(function ($record) use ($resource) {
                return $resource::getUrl('history', ['record' => $record]);
            });
    }
}
