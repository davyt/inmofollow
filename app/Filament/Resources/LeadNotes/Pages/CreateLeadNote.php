<?php

namespace App\Filament\Resources\LeadNotes\Pages;

use App\Filament\Resources\LeadNotes\LeadNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeadNote extends CreateRecord
{
    protected static string $resource = LeadNoteResource::class;
}
