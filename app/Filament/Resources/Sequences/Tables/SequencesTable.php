<?php

namespace App\Filament\Resources\Sequences\Tables;

use App\Models\Company;
use App\Models\LeadStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SequencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->label('Inmobiliaria')
                    ->formatStateUsing(fn ($state) => Company::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('lead_status_id')
                    ->label('Estado disparador')
                    ->formatStateUsing(fn ($state) => LeadStatus::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Secuencia')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(80)
                    ->searchable(),

                IconColumn::make('active')
                    ->label('Activa')
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