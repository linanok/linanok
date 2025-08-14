<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\History\RecordHistory;

class RoleHistory extends RecordHistory
{
    protected static string $resource = RoleResource::class;
}
