<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Filament\Resources\Obras\ObraResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObra extends EditRecord
{
    protected static string $resource = ObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
