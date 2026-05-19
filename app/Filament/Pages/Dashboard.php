<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InmofollowStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Escritorio';

    protected static ?string $title = 'Escritorio';

    protected static ?int $navigationSort = -10;

    public function getTitle(): string
    {
        return 'Escritorio';
    }

    public function getHeading(): string
    {
        return 'Resumen operativo';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAgent()
            ? 'Vista de tus leads, mensajes y actividad.'
            : 'Vista general del equipo comercial.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InmofollowStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}
