<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                ->default(fn () => config('inmofollow.default_company_id', 1))
                ->dehydrated(true),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),

                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->nullable(),

                Select::make('role')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Administrador',
                        'supervisor' => 'Supervisor',
                        'agent' => 'Agente',
                    ])
                    ->default('agent')
                    ->required(),

                Toggle::make('active')
                    ->label('Usuario activo')
                    ->default(true)
                    ->required(),

                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->helperText('En edición, dejar vacío para no cambiar la contraseña.'),
            ]);
    }
}