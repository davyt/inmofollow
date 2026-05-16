<?php

namespace App\Filament\Resources\Sequences\Schemas;

use App\Models\LeadStatus;
use App\Models\User;
use Filament\Forms\Components\Hidden;
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
                
                Select::make('scope')
                    ->label('Tipo')
                    ->options([
                        'global' => 'Global',
                        'personal' => 'Personal',
                    ])
                    ->default(fn () => auth()->user()?->isAgent() ? 'personal' : 'global')
                    ->disabled(fn () => auth()->user()?->isAgent())
                    ->dehydrated(true)
                    ->required(),
                
                Select::make('user_id')
                    ->label('Agente dueño')
                    ->helperText('Solo aplica si la secuencia es personal.')
                    ->options(fn () => User::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->nullable()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),
                
                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true)
                    ->visible(fn () => auth()->user()?->isAgent()),

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