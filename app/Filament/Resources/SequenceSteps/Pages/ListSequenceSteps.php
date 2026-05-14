<?php

namespace App\Filament\Resources\SequenceSteps\Pages;

use App\Filament\Resources\SequenceSteps\SequenceStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSequenceSteps extends ListRecords
{
    protected static string $resource = SequenceStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
