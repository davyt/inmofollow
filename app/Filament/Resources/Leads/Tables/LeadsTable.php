<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use App\Models\User;
use App\Services\FollowUpGenerator;
use App\Services\WhatsAppService;
use App\Support\Activity;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('user_id')
                    ->label('Agente')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '-')
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),

                TextColumn::make('lead_status_id')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => LeadStatus::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('property_type')
                    ->label('Tipo')
                    ->searchable(),

                TextColumn::make('zone')
                    ->label('Zona')
                    ->searchable(),

                TextColumn::make('source')
                    ->label('Origen')
                    ->searchable(),

                IconColumn::make('whatsapp_consent')
                    ->label('WhatsApp')
                    ->boolean(),

                IconColumn::make('email_consent')
                    ->label('Email')
                    ->boolean(),

                IconColumn::make('do_not_contact')
                    ->label('No contactar')
                    ->boolean(),

                TextColumn::make('next_follow_up_at')
                    ->label('Próximo seguimiento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($state): ?string => match(true) {
                        ! $state                                      => null,
                        Carbon::parse($state)->isToday()              => 'Para hoy',
                        Carbon::parse($state)->isPast()               => 'Vencido',
                        default                                        => null,
                    })
                    ->color(fn ($state): string => match(true) {
                        ! $state                                      => 'gray',
                        Carbon::parse($state)->isToday()              => 'warning',
                        Carbon::parse($state)->isPast()               => 'danger',
                        default                                        => 'success',
                    }),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('lead_status_id')
                    ->label('Estado')
                    ->options(fn () => LeadStatus::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('user_id')
                    ->label('Agente')
                    ->options(fn () => User::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),

                SelectFilter::make('zone')
                    ->label('Zona')
                    ->options(fn () => Lead::query()->whereNotNull('zone')->where('zone', '!=', '')->distinct()->orderBy('zone')->pluck('zone', 'zone')->toArray())
                    ->searchable(),

                SelectFilter::make('property_type')
                    ->label('Tipo de propiedad')
                    ->options(fn () => Lead::query()->whereNotNull('property_type')->where('property_type', '!=', '')->distinct()->orderBy('property_type')->pluck('property_type', 'property_type')->toArray())
                    ->searchable(),

                SelectFilter::make('source')
                    ->label('Origen')
                    ->options(fn () => Lead::query()->whereNotNull('source')->where('source', '!=', '')->distinct()->orderBy('source')->pluck('source', 'source')->toArray())
                    ->searchable(),

                Filter::make('whatsapp_consent')
                    ->label('Acepta WhatsApp')
                    ->query(fn (Builder $query): Builder => $query->where('whatsapp_consent', true)),

                Filter::make('do_not_contact')
                    ->label('No contactar')
                    ->query(fn (Builder $query): Builder => $query->where('do_not_contact', true)),

                Filter::make('overdue_followup')
                    ->label('Seguimiento vencido')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('next_follow_up_at')
                        ->where('next_follow_up_at', '<', now())
                        ->where('do_not_contact', false)
                    ),

                Filter::make('next_follow_up_range')
                    ->label('Rango seguimiento')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('next_follow_up_at', '>=', $v))
                            ->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('next_follow_up_at', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Seguimiento desde ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Seguimiento hasta ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('send_now')
                    ->label('Enviar ahora')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->modalHeading(fn (Lead $record): string => 'Enviar WhatsApp a ' . $record->name)
                    ->modalDescription('El mensaje se enviará inmediatamente vía WhatsApp Business API.')
                    ->visible(fn (Lead $record): bool =>
                        $record->whatsapp_consent &&
                        ! $record->do_not_contact &&
                        filled($record->phone)
                    )
                    ->form(function (Lead $record): array {
                        return [
                            Select::make('message_template_id')
                                ->label('Plantilla')
                                ->options(fn () => MessageTemplate::query()
                                    ->where('channel', 'whatsapp')
                                    ->where('active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                                )
                                ->searchable()
                                ->live()
                                ->required(),

                            Placeholder::make('preview')
                                ->label('Vista previa del mensaje')
                                ->content(function (Get $get) use ($record): string {
                                    $id = $get('message_template_id');
                                    if (! $id) {
                                        return '← Seleccioná una plantilla para ver el mensaje.';
                                    }
                                    $tpl = MessageTemplate::find($id);
                                    if (! $tpl) {
                                        return '-';
                                    }
                                    return str_replace(
                                        ['{{nombre}}', '{{zona}}', '{{tipo_propiedad}}', '{{agente}}'],
                                        [$record->name, $record->zone ?? '', $record->property_type ?? '', auth()->user()?->name ?? ''],
                                        $tpl->body,
                                    );
                                }),
                        ];
                    })
                    ->action(function (Lead $record, array $data): void {
                        $template = MessageTemplate::findOrFail($data['message_template_id']);
                        $body = str_replace(
                            ['{{nombre}}', '{{zona}}', '{{tipo_propiedad}}', '{{agente}}'],
                            [$record->name, $record->zone ?? '', $record->property_type ?? '', auth()->user()?->name ?? ''],
                            $template->body,
                        );

                        $company = Company::find($record->company_id);

                        if (! $company?->hasWhatsApp()) {
                            $hint = auth()->user()?->isAdmin()
                                ? 'Andá a Configuración → Mi empresa para completar las credenciales.'
                                : 'Pedile al administrador que configure WhatsApp en la empresa.';

                            Notification::make()
                                ->title('WhatsApp no configurado')
                                ->body($hint)
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $waId = app(WhatsAppService::class)->sendTextMessage($company, $record->phone, $body);

                            ScheduledMessage::create([
                                'lead_id'             => $record->id,
                                'message_template_id' => $template->id,
                                'user_id'             => auth()->id(),
                                'channel'             => 'whatsapp',
                                'message_body'        => $body,
                                'status'              => 'sent',
                                'scheduled_for'       => now(),
                                'sent_at'             => now(),
                                'wa_message_id'       => $waId ?: null,
                            ]);

                            $record->update(['last_contacted_at' => now()]);

                            Activity::log(
                                event: 'whatsapp_sent_now',
                                description: 'Mensaje enviado inmediatamente por WhatsApp.',
                                subject: $record,
                                properties: ['template' => $template->name, 'channel' => 'whatsapp'],
                            );

                            Notification::make()
                                ->title('Mensaje enviado')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error al enviar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('generate_followups')
                    ->label('Generar seguimiento')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Lead $record): void {
                        $created = app(FollowUpGenerator::class)->generateForLead($record);

                        Notification::make()
                            ->title($created > 0 ? 'Seguimiento generado' : 'No se generaron mensajes')
                            ->body($created > 0 ? "Se crearon {$created} mensaje(s) programado(s)." : 'Revisá que el lead tenga estado, consentimiento y una secuencia activa.')
                            ->success()
                            ->send();
                    }),

                Action::make('history')
                    ->label('Historial')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn (Lead $record): string => 'Historial de ' . $record->name)
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function (Lead $record) {
                        $notes = $record->notes()->with('user')->get()->map(fn ($n) => [
                            'type'  => 'note',
                            'date'  => $n->created_at,
                            'actor' => $n->user?->name ?? 'Sistema',
                            'text'  => $n->note,
                        ]);

                        $messages = $record->scheduledMessages()
                            ->whereIn('status', ['sent', 'failed'])
                            ->get()
                            ->map(fn ($m) => [
                                'type'    => 'message',
                                'date'    => $m->sent_at ?? $m->scheduled_for ?? $m->created_at,
                                'actor'   => null,
                                'channel' => $m->channel,
                                'status'  => $m->status,
                                'text'    => $m->message_body,
                            ]);

                        $activities = ActivityLog::query()
                            ->with('user')
                            ->where('subject_type', Lead::class)
                            ->where('subject_id', $record->id)
                            ->get()
                            ->map(fn ($a) => [
                                'type'  => 'activity',
                                'date'  => $a->created_at,
                                'actor' => $a->user?->name ?? 'Sistema',
                                'text'  => $a->description ?: $a->event,
                            ]);

                        $timeline = $notes->concat($messages)->concat($activities)
                            ->sortByDesc('date')
                            ->values();

                        return view('filament.modals.lead-history', [
                            'record'   => $record->load(['user', 'leadStatus']),
                            'timeline' => $timeline,
                        ]);
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
