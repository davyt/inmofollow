<?php

namespace App\Filament\Resources\ScheduledMessages\Pages;

use App\Filament\Resources\ScheduledMessages\ScheduledMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledMessage extends CreateRecord
{
    protected static string $resource = ScheduledMessageResource::class;
}
