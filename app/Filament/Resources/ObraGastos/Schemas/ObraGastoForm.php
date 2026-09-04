<?php

namespace App\Filament\Resources\ObraGastos\Schemas;

use App\Models\Obra;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ObraGastoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('obra_id')
                    ->label('Obra')
                    ->options(fn () => Obra::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('item')
                    ->label('Ítem')
                    ->required(),

                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->required(),

                DatePicker::make('expense_date')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
            ]);
    }
}
