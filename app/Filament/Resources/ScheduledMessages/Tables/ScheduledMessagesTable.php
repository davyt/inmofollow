<?php

namespace App\Filament\Resources\ScheduledMessages\Tables;

use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\User;
use App\Support\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ScheduledMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_for', 'asc')
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
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        default => $state ?? '-',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'cancelled' => 'Cancelado',
                        'failed' => 'Fallido',
                        default => $state ?? '-',
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                        'gray' => 'cancelled',
                        'danger' => 'failed',
                    ])
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
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'cancelled' => 'Cancelado',
                        'failed' => 'Fallido',
                    ]),
            
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ]),
            
                Filter::make('due_today')
                    ->label('Para hoy')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereDate('scheduled_for', now()->toDateString())
                    ),
            
                Filter::make('overdue')
                    ->label('Vencidos')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereNotNull('scheduled_for')
                        ->where('scheduled_for', '<', now())
                    ),
            
                Filter::make('upcoming')
                    ->label('Próximos')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereNotNull('scheduled_for')
                        ->where('scheduled_for', '>', now())
                    ),
            
                Filter::make('without_date')
                    ->label('Sin fecha')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereNull('scheduled_for')
                    ),
            ])
            ->recordActions([
                Action::make('open_whatsapp')
                ->label('Abrir WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->visible(fn (ScheduledMessage $record): bool => $record->channel === 'whatsapp')
                ->disabled(fn (ScheduledMessage $record): bool => blank($record->lead?->phone))
                ->tooltip(fn (ScheduledMessage $record): string => blank($record->lead?->phone)
                    ? 'El lead no tiene teléfono cargado'
                    : 'Abrir WhatsApp con el mensaje preparado'
                )
                ->url(function (ScheduledMessage $record): string {
                    $lead = $record->lead;
            
                    $phone = preg_replace('/\D+/', '', $lead?->phone ?? '');
            
                    // Si está cargado como 099123456, lo transforma a 59899123456
                    if (str_starts_with($phone, '09')) {
                        $phone = '598' . substr($phone, 1);
                    }
            
                    $message = rawurlencode($record->message_body);
            
                    return "https://wa.me/{$phone}?text={$message}";
                })
                ->openUrlInNewTab(),
            
                Action::make('mark_as_sent')
                    ->label('Marcar enviado')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledMessage $record): bool => $record->status === 'pending')
                    ->action(function (ScheduledMessage $record): void {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
            
                        $record->lead?->update([
                            'last_contacted_at' => now(),
                        ]);
                        
                        Activity::log(
                            event: 'scheduled_message_sent',
                            description: 'El mensaje programado fue marcado como enviado.',
                            subject: $record,
                            properties: [
                                'lead_id' => $record->lead_id,
                                'channel' => $record->channel,
                            ]
                        );
            
                        Notification::make()
                            ->title('Mensaje marcado como enviado')
                            ->success()
                            ->send();
                    }),
            
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}