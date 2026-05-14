<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Company;
use App\Models\LeadStatus;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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

                TextColumn::make('company_id')
                    ->label('Inmobiliaria')
                    ->formatStateUsing(fn ($state) => Company::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('user_id')
                    ->label('Agente')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '-')
                    ->sortable(),

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}