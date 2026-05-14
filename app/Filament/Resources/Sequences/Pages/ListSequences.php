<?php

namespace App\Filament\Resources\Sequences\Pages;

use App\Filament\Resources\Sequences\SequenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSequences extends ListRecords
{
    protected static string $resource = SequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
