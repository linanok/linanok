<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\History\RecordHistory;

class UserHistory extends RecordHistory
{
    protected static string $resource = UserResource::class;
}
