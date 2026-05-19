<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event')
                    ->label('Evento')
                    ->disabled(),

                TextInput::make('subject_label')
                    ->label('Registro')
                    ->disabled(),

                Textarea::make('description')
                    ->label('Descripción')
                    ->disabled()
                    ->columnSpanFull(),

                Textarea::make('properties')
                    ->label('Datos adicionales')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}