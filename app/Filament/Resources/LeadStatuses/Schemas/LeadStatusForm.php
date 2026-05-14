<?php

namespace App\Filament\Resources\LeadStatuses\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeadStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Inmobiliaria')
                    ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                TextInput::make('name')
                    ->label('Nombre del estado')
                    ->required(),

                TextInput::make('color')
                    ->label('Color')
                    ->default(null),

                Toggle::make('starts_sequence')
                    ->label('Inicia secuencia')
                    ->default(false)
                    ->required(),

                Toggle::make('stops_sequence')
                    ->label('Detiene secuencia')
                    ->default(false)
                    ->required(),

                Toggle::make('is_final')
                    ->label('Estado final')
                    ->default(false)
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Orden')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}