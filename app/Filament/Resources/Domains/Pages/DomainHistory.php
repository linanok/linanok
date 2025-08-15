<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\History\RecordHistory;

class DomainHistory extends RecordHistory
{
    protected static string $resource = DomainResource::class;
}
