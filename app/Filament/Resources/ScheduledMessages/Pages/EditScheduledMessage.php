<?php

namespace App\Filament\Resources\ScheduledMessages\Pages;

use App\Filament\Resources\ScheduledMessages\ScheduledMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledMessage extends EditRecord
{
    protected static string $resource = ScheduledMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
