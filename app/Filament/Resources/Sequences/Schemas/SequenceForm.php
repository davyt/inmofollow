<?php

namespace App\Filament\Resources\Sequences\Schemas;

use Filament\Forms\Components\Hidden;
use App\Models\LeadStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                ->default(fn () => config('inmofollow.default_company_id', 1))
                ->dehydrated(true),

                Select::make('lead_status_id')
                    ->label('Estado que dispara la secuencia')
                    ->options(fn () => LeadStatus::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                TextInput::make('name')
                    ->label('Nombre de la secuencia')
                    ->required(),

                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->nullable(),

                Toggle::make('active')
                    ->label('Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}