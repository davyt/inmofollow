<?php

namespace App\Filament\Resources\Sequences\Tables;

use App\Models\LeadStatus;
use App\Filament\Resources\Sequences\SequenceResource;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Support\Activity;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
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
                Action::make('view_readonly')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Detalle de secuencia')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn ($record) => view('filament.modals.sequence-readonly', [
                        'record' => $record,
                        'canEdit' => SequenceResource::canEdit($record),
                    ])),
                    
                Action::make('duplicate_as_personal')
                    ->label('Duplicar como personal')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Sequence $record): bool =>
                        auth()->user()?->isAgent()
                        && $record->scope === 'global'
                    )
                    ->action(function (Sequence $record): void {
                        $copy = Sequence::create([
                            'company_id' => $record->company_id,
                            'user_id' => auth()->id(),
                            'scope' => 'personal',
                            'lead_status_id' => $record->lead_status_id,
                            'name' => $record->name . ' (copia personal)',
                            'description' => $record->description,
                            'active' => true,
                        ]);
                
                        foreach ($record->steps()->orderBy('sort_order')->orderBy('day_offset')->get() as $step) {
                            SequenceStep::create([
                                'sequence_id' => $copy->id,
                                'message_template_id' => $step->message_template_id,
                                'day_offset' => $step->day_offset,
                                'channel' => $step->channel,
                                'sort_order' => $step->sort_order,
                                'active' => $step->active,
                            ]);
                        }
                
                        Activity::log(
                            event: 'sequence_duplicated_as_personal',
                            description: 'Se duplicó una secuencia global como personal.',
                            subject: $copy,
                            properties: [
                                'original_sequence_id' => $record->id,
                            ]
                        );
                
                        Notification::make()
                            ->title('Secuencia duplicada')
                            ->body('Se creó una copia personal editable con sus pasos.')
                            ->success()
                            ->send();
                    }),
            
                EditAction::make()
                    ->visible(fn ($record): bool => SequenceResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),
            ]);
    }
}