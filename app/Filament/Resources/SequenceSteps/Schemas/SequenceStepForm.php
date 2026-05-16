<?php

namespace App\Filament\Resources\SequenceSteps\Schemas;

use App\Models\MessageTemplate;
use App\Models\Sequence;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SequenceStepForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sequence_id')
                    ->label('Secuencia')
                    ->options(function () {
                        $query = Sequence::query()->orderBy('name');
                
                        $user = auth()->user();
                
                        if ($user?->isAgent()) {
                            $query
                                ->where('scope', 'personal')
                                ->where('user_id', $user->id);
                        }
                
                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->required(),

                Select::make('message_template_id')
                    ->label('Plantilla')
                    ->options(function () {
                        $query = MessageTemplate::query()->orderBy('name');
                
                        $user = auth()->user();
                
                        if ($user?->isAgent()) {
                            $query->where(function ($query) use ($user) {
                                $query
                                    ->where('scope', 'global')
                                    ->orWhere('user_id', $user->id);
                            });
                        }
                
                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->nullable(),

                TextInput::make('day_offset')
                    ->label('Días desde el inicio')
                    ->helperText('Ejemplo: 0 = hoy, 7 = dentro de una semana.')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->default('whatsapp')
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Toggle::make('active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }
}