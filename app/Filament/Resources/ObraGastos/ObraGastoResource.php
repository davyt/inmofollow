<?php

namespace App\Filament\Resources\ObraGastos;

use App\Filament\Resources\ObraGastos\Pages\CreateObraGasto;
use App\Filament\Resources\ObraGastos\Pages\EditObraGasto;
use App\Filament\Resources\ObraGastos\Pages\ListObraGastos;
use App\Filament\Resources\ObraGastos\Schemas\ObraGastoForm;
use App\Filament\Resources\ObraGastos\Tables\ObraGastosTable;
use App\Models\ObraGasto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ObraGastoResource extends Resource
{
    protected static ?string $model = ObraGasto::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'gasto';

    protected static ?string $pluralModelLabel = 'Gastos';

    protected static ?string $navigationLabel = 'Gastos';

    public static function getNavigationGroup(): ?string
    {
        return 'Rentiva';
    }

    public static function form(Schema $schema): Schema
    {
        return ObraGastoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObraGastosTable::configure($table);
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
            'index' => ListObraGastos::route('/'),
            'create' => CreateObraGasto::route('/create'),
            'edit' => EditObraGasto::route('/{record}/edit'),
        ];
    }
}
