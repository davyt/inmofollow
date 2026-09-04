<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Filament\Resources\Obras\ObraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObras extends ListRecords
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
