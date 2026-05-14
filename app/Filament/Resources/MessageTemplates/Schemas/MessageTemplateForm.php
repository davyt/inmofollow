<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MessageTemplateForm
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
                    ->label('Nombre de la plantilla')
                    ->required(),

                Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->default('whatsapp')
                    ->required(),

                TextInput::make('subject')
                    ->label('Asunto')
                    ->helperText('Solo aplica para email.')
                    ->nullable(),

                Textarea::make('body')
                    ->label('Mensaje')
                    ->helperText('Variables disponibles: {{nombre}}, {{zona}}, {{tipo_propiedad}}, {{agente}}')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),

                Toggle::make('active')
                    ->label('Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}