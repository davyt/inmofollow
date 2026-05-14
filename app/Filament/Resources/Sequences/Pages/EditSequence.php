<?php

namespace App\Filament\Resources\Sequences\Pages;

use App\Filament\Resources\Sequences\SequenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSequence extends EditRecord
{
    protected static string $resource = SequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
