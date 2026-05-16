<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\LeadStatus;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Lead;
use App\Services\FollowUpGenerator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

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
                    ->dateTime()
                    ->sortable(),

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
                //
            ])
            ->recordActions([
                Action::make('generate_followups')
                    ->label('Generar seguimiento')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Lead $record): void {
                        $created = app(FollowUpGenerator::class)->generateForLead($record);
            
                        Notification::make()
                            ->title($created > 0 ? 'Seguimiento generado' : 'No se generaron mensajes')
                            ->body($created > 0 ? "Se crearon {$created} mensaje(s) programado(s)." : 'Revisá que el lead tenga estado, consentimiento y una secuencia activa.')
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