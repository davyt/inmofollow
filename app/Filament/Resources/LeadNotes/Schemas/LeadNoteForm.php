<?php

namespace App\Filament\Resources\LeadNotes\Schemas;

use App\Models\Lead;
use Filament\Forms\Components\Hidden;
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
                    ->options(function () {
                        $query = Lead::query()->orderBy('name');

                        $user = auth()->user();

                        if ($user?->isAgent()) {
                            $query->where('user_id', $user->id);
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->required(),

                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true),

                Textarea::make('note')
                    ->label('Nota')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}