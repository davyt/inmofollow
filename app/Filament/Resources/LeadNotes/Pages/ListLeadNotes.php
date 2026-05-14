<?php

namespace App\Filament\Resources\LeadNotes\Pages;

use App\Filament\Resources\LeadNotes\LeadNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeadNotes extends ListRecords
{
    protected static string $resource = LeadNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
