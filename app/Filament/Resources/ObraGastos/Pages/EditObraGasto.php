<?php

namespace App\Filament\Resources\ObraGastos\Pages;

use App\Filament\Resources\ObraGastos\ObraGastoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObraGasto extends EditRecord
{
    protected static string $resource = ObraGastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
