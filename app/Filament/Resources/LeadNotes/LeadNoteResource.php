<?php

namespace App\Filament\Resources\LeadNotes;

use App\Filament\Resources\LeadNotes\Pages\CreateLeadNote;
use App\Filament\Resources\LeadNotes\Pages\EditLeadNote;
use App\Filament\Resources\LeadNotes\Pages\ListLeadNotes;
use App\Filament\Resources\LeadNotes\Schemas\LeadNoteForm;
use App\Filament\Resources\LeadNotes\Tables\LeadNotesTable;
use App\Models\LeadNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeadNoteResource extends Resource
{
    protected static ?string $model = LeadNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LeadNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadNotes::route('/'),
            'create' => CreateLeadNote::route('/create'),
            'edit' => EditLeadNote::route('/{record}/edit'),
        ];
    }
}
