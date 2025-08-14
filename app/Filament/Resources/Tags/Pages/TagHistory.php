<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Links\LinkResource;
use App\History\RecordHistory;

class TagHistory extends RecordHistory
{
    protected static string $resource = LinkResource::class;
}
