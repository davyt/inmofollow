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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SequenceStepResource extends Resource
{
    protected static ?string $model = SequenceStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'paso de secuencia';
    
    protected static ?string $pluralModelLabel = 'pasos de secuencia';
    
    protected static ?string $navigationLabel = 'Pasos de secuencia';
    
    protected static ?int $navigationSort = 30;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Automatizaciones';
    }
    
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
    
        if (! $user) {
            return false;
        }
    
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }
    
        return $record->sequence?->scope === 'personal'
            && (int) $record->sequence?->user_id === (int) $user->id;
    }
    
    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

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
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
    
        $user = auth()->user();
    
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
    
        if ($user->isAdmin() || $user->isSupervisor()) {
            return $query;
        }
    
        return $query->whereHas('sequence', function (Builder $sequenceQuery) use ($user) {
            $sequenceQuery
                ->where('scope', 'personal')
                ->where('user_id', $user->id);
        });
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
