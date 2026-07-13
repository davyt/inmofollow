<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use App\Services\FollowUpGenerator;
use App\Support\Activity;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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

                IconColumn::make('do_not_contact')
                    ->label('No contactar')
                    ->boolean(),

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

                TernaryFilter::make('contacted')
                    ->label('Contactado')
                    ->placeholder('Todos')
                    ->trueLabel('Contactado (plantilla enviada)')
                    ->falseLabel('No contactado')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas(
                            'scheduledMessages',
                            fn (Builder $q) => $q->where('status', 'sent'),
                        ),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave(
                            'scheduledMessages',
                            fn (Builder $q) => $q->where('status', 'sent'),
                        ),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                RestoreAction::make()
                    ->visible(fn (Lead $record): bool => $record->trashed()),

                Action::make('generate_followups')
                    ->label('Generar seguimiento')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Lead $record): bool => ! $record->trashed())
                    ->action(function (Lead $record): void {
                        $created = app(FollowUpGenerator::class)->generateForLead($record);

                        Notification::make()
                            ->title($created > 0 ? 'Seguimiento generado' : 'No se generaron mensajes')
                            ->body($created > 0 ? "Se crearon {$created} mensaje(s) programado(s)." : 'Revisá que el lead tenga estado, consentimiento y una secuencia activa.')
                            ->success()
                            ->send();
                    }),

                Action::make('conversation')
                    ->label('Conversación')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (Lead $record): bool => ! $record->trashed())
                    ->modalHeading(fn (Lead $record): string => 'Conversación con ' . $record->name)
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (Lead $record) => view('filament.modals.lead-conversation-wrapper', [
                        'lead' => $record,
                    ])),

                Action::make('history')
                    ->label('Actividad')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->visible(fn (Lead $record): bool => ! $record->trashed())
                    ->modalHeading(fn (Lead $record): string => 'Actividad de ' . $record->name)
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

                        $timeline = $notes->concat($activities)
                            ->sortByDesc('date')
                            ->values();

                        return view('filament.modals.lead-history', [
                            'record'   => $record->load(['user', 'leadStatus']),
                            'timeline' => $timeline,
                        ]);
                    }),

                EditAction::make()
                    ->visible(fn (Lead $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_agent')
                        ->label('Asignar agente')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor())
                        ->form([
                            Select::make('user_id')
                                ->label('Agente')
                                ->options(fn () => User::query()
                                    ->where('active', true)
                                    ->whereIn('role', ['agent', 'supervisor'])
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                                )
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $agent = User::find($data['user_id']);
                            $records->each->update(['user_id' => $data['user_id']]);
                            Notification::make()
                                ->title("{$records->count()} lead(s) asignados a {$agent->name}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_edit')
                        ->label('Editar en lote')
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor())
                        ->form([
                            TextInput::make('source')
                                ->label('Origen')
                                ->placeholder('Dejar vacío para no cambiar'),

                            Select::make('whatsapp_consent')
                                ->label('Acepta WhatsApp')
                                ->options(['' => '— No cambiar —', '1' => 'Sí', '0' => 'No'])
                                ->default(''),

                            Select::make('email_consent')
                                ->label('Acepta Email')
                                ->options(['' => '— No cambiar —', '1' => 'Sí', '0' => 'No'])
                                ->default(''),

                            Select::make('do_not_contact')
                                ->label('No contactar')
                                ->options(['' => '— No cambiar —', '1' => 'Sí', '0' => 'No'])
                                ->default(''),

                            Select::make('lead_status_id')
                                ->label('Estado')
                                ->options(fn () => LeadStatus::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                                ->placeholder('— No cambiar —'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $updates = [];

                            if (filled($data['source'] ?? null)) {
                                $updates['source'] = $data['source'];
                            }

                            foreach (['whatsapp_consent', 'email_consent', 'do_not_contact'] as $field) {
                                if (($data[$field] ?? '') !== '') {
                                    $updates[$field] = $data[$field] === '1';
                                }
                            }

                            if (filled($data['lead_status_id'] ?? null)) {
                                $updates['lead_status_id'] = $data['lead_status_id'];
                            }

                            if (empty($updates)) {
                                Notification::make()->title('No seleccionaste ningún cambio')->warning()->send();
                                return;
                            }

                            $records->each->update($updates);

                            Notification::make()
                                ->title("{$records->count()} lead(s) actualizados")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
