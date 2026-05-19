<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\ScheduledMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InmofollowStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $leadQuery = Lead::query();
        $messageQuery = ScheduledMessage::query();
        $activityQuery = ActivityLog::query();

        if ($user?->isAgent()) {
            $leadQuery->where('user_id', $user->id);
            $messageQuery->where('user_id', $user->id);
            $activityQuery->where('user_id', $user->id);
        }

        $leadsTotal = (clone $leadQuery)->count();

        $leadsContactables = (clone $leadQuery)
            ->where('do_not_contact', false)
            ->count();

        $pendingMessages = (clone $messageQuery)
            ->where('status', 'pending')
            ->count();

        $dueToday = (clone $messageQuery)
            ->where('status', 'pending')
            ->whereDate('scheduled_for', now()->toDateString())
            ->count();

        $overdue = (clone $messageQuery)
            ->where('status', 'pending')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<', now())
            ->count();

        $sentThisWeek = (clone $messageQuery)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->startOfWeek())
            ->count();

        $activityToday = (clone $activityQuery)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return [
            Stat::make($user?->isAgent() ? 'Mis leads' : 'Leads totales', $leadsTotal)
                ->description('Propietarios/leads registrados'),

            Stat::make('Contactables', $leadsContactables)
                ->description('Leads sin bloqueo de contacto'),

            Stat::make('Mensajes pendientes', $pendingMessages)
                ->description('Seguimientos pendientes'),

            Stat::make('Para hoy', $dueToday)
                ->description('Mensajes pendientes de hoy'),

            Stat::make('Vencidos', $overdue)
                ->description('Pendientes con fecha anterior'),

            Stat::make('Enviados esta semana', $sentThisWeek)
                ->description('Mensajes marcados como enviados'),

            Stat::make('Actividad hoy', $activityToday)
                ->description($user?->isAgent() ? 'Tus acciones registradas' : 'Acciones registradas hoy'),
        ];
    }
}