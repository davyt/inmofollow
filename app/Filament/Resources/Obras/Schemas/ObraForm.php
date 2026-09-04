<?php

namespace App\Filament\Resources\Obras\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ObraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la obra')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}
