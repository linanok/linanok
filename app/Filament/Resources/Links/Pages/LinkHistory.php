<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use App\History\RecordHistory;

class LinkHistory extends RecordHistory
{
    protected static string $resource = LinkResource::class;
}
