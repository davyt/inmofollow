<?php

namespace App\Filament\Resources\LeadNotes\Pages;

use App\Filament\Resources\LeadNotes\LeadNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeadNote extends EditRecord
{
    protected static string $resource = LeadNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
