<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('user_id')
                    ->label('Usuario')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? 'Sistema')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject_label')
                    ->label('Registro')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(90)
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->visible(fn (): bool => auth()->user()?->isAdmin()),
            ]);
    }
}