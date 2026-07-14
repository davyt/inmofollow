<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InmofollowStatsOverview;
use App\Filament\Widgets\LeadClassificationWidget;
use App\Filament\Widgets\QuickActionsWidget;
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
        $hour = now()->hour;
        $greeting = match(true) {
            $hour < 12 => 'Buenos días',
            $hour < 19 => 'Buenas tardes',
            default    => 'Buenas noches',
        };
        return $greeting . ', ' . (auth()->user()?->name ?? '') . '.';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->isAgent()
            ? 'Aquí están tus leads y seguimientos pendientes para hoy.'
            : 'Vista general del equipo comercial.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            InmofollowStatsOverview::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            LeadClassificationWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
