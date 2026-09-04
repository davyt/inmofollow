<?php

namespace App\Filament\Resources\Obras;

use App\Filament\Resources\Obras\Pages\CreateObra;
use App\Filament\Resources\Obras\Pages\EditObra;
use App\Filament\Resources\Obras\Pages\ListObras;
use App\Filament\Resources\Obras\Schemas\ObraForm;
use App\Filament\Resources\Obras\Tables\ObrasTable;
use App\Models\Obra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ObraResource extends Resource
{
    protected static ?string $model = Obra::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'obra';

    protected static ?string $pluralModelLabel = 'Obras';

    protected static ?string $navigationLabel = 'Obras';

    public static function getNavigationGroup(): ?string
    {
        return 'Rentiva';
    }

    public static function form(Schema $schema): Schema
    {
        return ObraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObrasTable::configure($table);
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
            'index' => ListObras::route('/'),
            'create' => CreateObra::route('/create'),
            'edit' => EditObra::route('/{record}/edit'),
        ];
    }
}
