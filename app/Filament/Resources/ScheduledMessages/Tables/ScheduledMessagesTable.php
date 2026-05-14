<?php

namespace App\Filament\Resources\ScheduledMessages\Tables;

use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduledMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_id')
                    ->label('Lead')
                    ->formatStateUsing(fn ($state) => Lead::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('sequence_id')
                    ->label('Secuencia')
                    ->formatStateUsing(fn ($state) => Sequence::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('sequence_step_id')
                    ->label('Paso')
                    ->formatStateUsing(fn ($state) => SequenceStep::find($state)?->id ? 'Paso #' . $state : '-')
                    ->sortable(),

                TextColumn::make('message_template_id')
                    ->label('Plantilla')
                    ->formatStateUsing(fn ($state) => MessageTemplate::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('user_id')
                    ->label('Agente')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'cancelled' => 'Cancelado',
                        'failed' => 'Fallido',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('message_body')
                    ->label('Mensaje')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('scheduled_for')
                    ->label('Programado para')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('Enviado el')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}