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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SequenceResource extends Resource
{
    protected static ?string $model = Sequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $modelLabel = 'secuencia';
    
    protected static ?string $pluralModelLabel = 'secuencias';
    
    protected static ?string $navigationLabel = 'Secuencias';
    
    protected static ?int $navigationSort = 20;
    
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
    
        return $record->scope === 'personal'
            && (int) $record->user_id === (int) $user->id;
    }
    
    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

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
    
        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('scope', 'global')
                ->orWhere('user_id', $user->id);
        });
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
