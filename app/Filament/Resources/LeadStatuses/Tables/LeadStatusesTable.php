<?php

namespace App\Filament\Resources\LeadStatuses\Tables;

use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->label('Inmobiliaria')
                    ->formatStateUsing(fn ($state) => Company::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Color')
                    ->searchable(),

                IconColumn::make('starts_sequence')
                    ->label('Inicia secuencia')
                    ->boolean(),

                IconColumn::make('stops_sequence')
                    ->label('Detiene secuencia')
                    ->boolean(),

                IconColumn::make('is_final')
                    ->label('Final')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->numeric()
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