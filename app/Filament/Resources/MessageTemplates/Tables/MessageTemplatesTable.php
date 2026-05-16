<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Plantilla')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('body')
                    ->label('Mensaje')
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