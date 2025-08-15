<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\Domain;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (Domain $record) {
                    try {
                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Domain deleted successfully')
                            ->send();

                        return redirect()->route('filament.admin.resources.domains.index');
                    } catch (QueryException $e) {
                        if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed') ||
                            str_contains($e->getMessage(), 'foreign key constraint')) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete domain')
                                ->body('This domain cannot be deleted because it has links attached to it. Please remove all links from this domain first.')
                                ->send();
                        } else {
                            throw $e;
                        }
                    }
                })
                ->requiresConfirmation(),
        ];
    }

    protected function beforeSave(): void
    {
        if (! $this->data['is_admin_panel_active']) {
            $otherAdminPanelExists = Domain::adminPanelAvailable()
                ->where('id', '!=', $this->record->id)
                ->exists();

            if (! $otherAdminPanelExists) {
                if (! $this->data['is_admin_panel_active']) {
                    $this->addError('data.is_admin_panel_active', 'At least one domain must have the admin panel activated.');
                }

                Notification::make()
                    ->danger()
                    ->title('Cannot disable admin panel')
                    ->body('At least one domain must have the admin panel activated.')
                    ->send();

                $this->halt();
            }
        }
    }
}
