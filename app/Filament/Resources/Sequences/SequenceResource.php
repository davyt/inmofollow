<?php

namespace App\Filament\Resources\Sequences;

use App\Filament\Resources\Sequences\Pages\CreateSequence;
use App\Filament\Resources\Sequences\Pages\EditSequence;
use App\Filament\Resources\Sequences\Pages\ListSequences;
use App\Filament\Resources\Sequences\Schemas\SequenceForm;
use App\Filament\Resources\Sequences\Tables\SequencesTable;
use App\Models\Sequence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SequenceResource extends Resource
{
    protected static ?string $model = Sequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SequenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SequencesTable::configure($table);
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
            'index' => ListSequences::route('/'),
            'create' => CreateSequence::route('/create'),
            'edit' => EditSequence::route('/{record}/edit'),
        ];
    }
}
