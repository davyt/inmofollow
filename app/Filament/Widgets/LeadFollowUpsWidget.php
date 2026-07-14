<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LeadFollowUpsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Leads a contactar';

    public static function canView(): bool
    {
        return false;
    }

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->defaultSort('next_follow_up_at', 'asc')
            ->paginated([5, 10, 25])
            ->emptyStateHeading('Todo al día')
            ->emptyStateDescription('No hay leads con seguimiento pendiente para hoy.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('next_follow_up_at')
                    ->label('Seguimiento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => Carbon::parse($state)->isPast() ? 'danger' : 'warning'),

                TextColumn::make('name')
                    ->label('Lead')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('-'),

                TextColumn::make('zone')
                    ->label('Zona')
                    ->placeholder('-'),

                TextColumn::make('property_type')
                    ->label('Tipo')
                    ->placeholder('-'),

                TextColumn::make('leadStatus.name')
                    ->label('Estado')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('user.name')
                    ->label('Agente')
                    ->placeholder('Sin asignar')
                    ->visible(fn (): bool => ! auth()->user()?->isAgent()),
            ])
            ->recordActions([
                Action::make('ir_al_lead')
                    ->label('Ver lead')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Lead $record): string => LeadResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    private function getQuery(): Builder
    {
        $user = auth()->user();

        $query = Lead::query()
            ->with(['leadStatus', 'user'])
            ->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now()->endOfDay());

        if ($user?->isAgent()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
