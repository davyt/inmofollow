<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use Filament\Actions\Action;
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
                Action::make('view_readonly')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Detalle de plantilla')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn ($record) => view('filament.modals.message-template-readonly', [
                        'record' => $record,
                        'canEdit' => MessageTemplateResource::canEdit($record),
                    ])),
            
                EditAction::make()
                    ->visible(fn ($record): bool => MessageTemplateResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),
            ]);
    }
}