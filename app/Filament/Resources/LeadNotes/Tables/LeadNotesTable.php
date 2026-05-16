<?php

namespace App\Filament\Resources\LeadNotes\Tables;

use App\Models\Lead;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_id')
                    ->label('Lead')
                    ->formatStateUsing(fn ($state) => Lead::find($state)?->name ?? $state)
                    ->sortable(),

                TextColumn::make('user_id')
                    ->label('Agente')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '-')
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),

                TextColumn::make('note')
                    ->label('Nota')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),

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