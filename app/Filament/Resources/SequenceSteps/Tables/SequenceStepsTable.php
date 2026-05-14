<?php

namespace App\Filament\Resources\SequenceSteps\Tables;

use App\Models\MessageTemplate;
use App\Models\Sequence;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SequenceStepsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence_id')
                    ->label('Secuencia')
                    ->formatStateUsing(fn ($state) => Sequence::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('message_template_id')
                    ->label('Plantilla')
                    ->formatStateUsing(fn ($state) => MessageTemplate::find($state)?->name ?? '-')
                    ->sortable(),

                TextColumn::make('day_offset')
                    ->label('Días')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('active')
                    ->label('Activo')
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