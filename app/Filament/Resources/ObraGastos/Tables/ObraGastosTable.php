<?php

namespace App\Filament\Resources\ObraGastos\Tables;

use App\Models\Obra;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ObraGastosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obra.name')
                    ->label('Obra')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item')
                    ->label('Ítem')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Monto')
                    ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))
                    ),

                TextColumn::make('expense_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('obra_id')
                    ->label('Obra')
                    ->options(fn () => Obra::pluck('name', 'id')),
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
