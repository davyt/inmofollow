<?php

namespace App\Filament\Resources\SequenceSteps\Pages;

use App\Filament\Resources\SequenceSteps\SequenceStepResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSequenceStep extends EditRecord
{
    protected static string $resource = SequenceStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
