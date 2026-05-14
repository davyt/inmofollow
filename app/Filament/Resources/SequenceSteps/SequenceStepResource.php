<?php

namespace App\Filament\Resources\SequenceSteps;

use App\Filament\Resources\SequenceSteps\Pages\CreateSequenceStep;
use App\Filament\Resources\SequenceSteps\Pages\EditSequenceStep;
use App\Filament\Resources\SequenceSteps\Pages\ListSequenceSteps;
use App\Filament\Resources\SequenceSteps\Schemas\SequenceStepForm;
use App\Filament\Resources\SequenceSteps\Tables\SequenceStepsTable;
use App\Models\SequenceStep;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SequenceStepResource extends Resource
{
    protected static ?string $model = SequenceStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SequenceStepForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SequenceStepsTable::configure($table);
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
            'index' => ListSequenceSteps::route('/'),
            'create' => CreateSequenceStep::route('/create'),
            'edit' => EditSequenceStep::route('/{record}/edit'),
        ];
    }
}
