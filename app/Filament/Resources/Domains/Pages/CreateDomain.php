<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fill_with_current_domain_data')
                ->label('Fill With Current Domain Data')
                ->action(function () {
                    $request = request();
                    $host = $request->getHttpHost();
                    $protocol = $request->getScheme();

                    $this->form->fill([
                        'host' => $host,
                        'protocol' => $protocol,
                        'is_active' => true,
                        'is_admin_panel_active' => true,
                    ]);
                })
                ->visible(fn () => ! Domain::exists()),
        ];
    }
}
