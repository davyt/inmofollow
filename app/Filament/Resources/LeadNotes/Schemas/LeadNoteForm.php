<?php

namespace App\Filament\Resources\LeadNotes\Schemas;

use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LeadNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lead_id')
                    ->label('Propietario / Lead')
                    ->options(fn () => Lead::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->required(),

                Select::make('user_id')
                    ->label('Agente / Usuario')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->default(auth()->id())
                    ->nullable(),

                Textarea::make('note')
                    ->label('Nota')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}