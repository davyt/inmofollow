<?php

namespace App\Filament\Resources\ScheduledMessages\Schemas;

use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;

class ScheduledMessageForm
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

                Select::make('sequence_id')
                    ->label('Secuencia')
                    ->options(fn () => Sequence::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                Select::make('sequence_step_id')
                    ->label('Paso de secuencia')
                    ->options(fn () => SequenceStep::query()->orderBy('sort_order')->pluck('id', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                Select::make('message_template_id')
                    ->label('Plantilla')
                    ->options(fn () => MessageTemplate::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                Select::make('user_id')
                    ->label('Agente')
                    ->options(fn () => User::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->default(auth()->id())
                    ->nullable()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),
                
                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true)
                    ->visible(fn () => auth()->user()?->isAgent()),

                Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->default('whatsapp')
                    ->required(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'cancelled' => 'Cancelado',
                        'failed' => 'Fallido',
                    ])
                    ->default('pending')
                    ->required(),

                DateTimePicker::make('scheduled_for')
                    ->label('Programado para')
                    ->nullable(),

                DateTimePicker::make('sent_at')
                    ->label('Enviado el')
                    ->nullable(),

                Textarea::make('message_body')
                    ->label('Mensaje')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
            ]);
    }
}