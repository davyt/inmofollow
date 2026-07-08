<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Models\MessageTemplate;
use App\Support\Activity;
use Filament\Notifications\Notification;
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

                TextColumn::make('meta_status')
                    ->label('Estado Meta')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'APPROVED'       => 'success',
                        'PENDING',
                        'PENDING_REVIEW' => 'warning',
                        'REJECTED'       => 'danger',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'APPROVED'       => 'Aprobada',
                        'PENDING'        => 'Pendiente',
                        'PENDING_REVIEW' => 'En revisión',
                        'REJECTED'       => 'Rechazada',
                        'PAUSED'         => 'Pausada',
                        'DISABLED'       => 'Desactivada',
                        null             => 'Sin sync',
                        default          => $state,
                    })
                    ->placeholder('—'),

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
                
                Action::make('duplicate_as_personal')
                    ->label('Duplicar como personal')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (MessageTemplate $record): bool =>
                        auth()->user()?->isAgent()
                        && $record->scope === 'global'
                    )
                    ->action(function (MessageTemplate $record): void {
                        $copy = MessageTemplate::create([
                            'company_id' => $record->company_id,
                            'user_id' => auth()->id(),
                            'scope' => 'personal',
                            'name' => $record->name . ' (copia personal)',
                            'channel' => $record->channel,
                            'subject' => $record->subject,
                            'body' => $record->body,
                            'active' => true,
                        ]);
                
                        Activity::log(
                            event: 'message_template_duplicated_as_personal',
                            description: 'Se duplicó una plantilla global como personal.',
                            subject: $copy,
                            properties: [
                                'original_template_id' => $record->id,
                            ]
                        );
                
                        Notification::make()
                            ->title('Plantilla duplicada')
                            ->body('Se creó una copia personal editable.')
                            ->success()
                            ->send();
                    }),
                
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