<?php

namespace App\Filament\Resources\ObraGastos\Pages;

use App\Filament\Resources\ObraGastos\ObraGastoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObraGastos extends ListRecords
{
    protected static string $resource = ObraGastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
