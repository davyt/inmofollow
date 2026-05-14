<?php

namespace App\Filament\Resources\LeadStatuses\Pages;

use App\Filament\Resources\LeadStatuses\LeadStatusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeadStatus extends EditRecord
{
    protected static string $resource = LeadStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
