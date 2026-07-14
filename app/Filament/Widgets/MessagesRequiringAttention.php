<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\ScheduledMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MessagesRequiringAttention extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Mensajes que requieren atención';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getMessagesQuery())
            ->defaultSort('scheduled_for', 'asc')
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('scheduled_for')
                    ->label('Programado para')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Sin fecha'),

                TextColumn::make('lead.name')
                    ->label('Lead')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('lead.phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        default => $state ?? '-',
                    }),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'cancelled' => 'Cancelado',
                        'failed' => 'Fallido',
                        default => $state ?? '-',
                    }),

                TextColumn::make('message_body')
                    ->label('Mensaje')
                    ->limit(80)
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('ver_lead')
                    ->label('Ver lead')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ScheduledMessage $record): string => $record->lead_id
                        ? LeadResource::getUrl('edit', ['record' => $record->lead_id])
                        : '#'
                    ),
            ]);
    }

    private function getMessagesQuery(): Builder
    {
        $query = ScheduledMessage::query()
            ->with('lead')
            ->where('status', 'pending')
            ->where(function (Builder $query) {
                $query
                    ->whereNull('scheduled_for')
                    ->orWhereDate('scheduled_for', '<=', now()->toDateString());
            });

        $user = auth()->user();

        if ($user?->isAgent()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}