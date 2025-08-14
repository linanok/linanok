<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;

    public function mount(): void
    {
        parent::mount();

        // Check if we have URL parameters to pre-fill the form
        if (session()->has('original_url')) {
            // Fill the form with the URL parameter
            $this->form->fill([
                'original_url' => session()->get('original_url'),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
