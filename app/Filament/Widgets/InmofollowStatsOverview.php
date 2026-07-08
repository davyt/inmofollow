<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\WaInboundMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InmofollowStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $leadQuery    = Lead::query();
        $messageQuery = ScheduledMessage::query();

        if ($user?->isAgent()) {
            $leadQuery->where('user_id', $user->id);
            $messageQuery->where('user_id', $user->id);
        }

        $contactable = (clone $leadQuery)
            ->where('do_not_contact', false)
            ->count();

        $overdueFollowUps = (clone $leadQuery)
            ->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now()->startOfDay())
            ->count();

        $followUpsToday = (clone $leadQuery)
            ->where('do_not_contact', false)
            ->whereNotNull('next_follow_up_at')
            ->whereDate('next_follow_up_at', today())
            ->count();

        $sentThisWeek = (clone $messageQuery)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->startOfWeek())
            ->count();

        // Mensajes WhatsApp entrantes sin respuesta en las últimas 48h
        $unrepliedQuery = WaInboundMessage::query()
            ->where('received_at', '>=', now()->subHours(48));
        if ($user?->isAgent()) {
            $unrepliedQuery->whereHas('lead', fn ($q) => $q->where('user_id', $user->id));
        }
        // Sin responder = no hay scheduled_message posterior del mismo lead
        $unreplied = $unrepliedQuery->whereDoesntHave('lead.scheduledMessages', function ($q) {
            $q->where('status', 'sent')->whereColumn('sent_at', '>', 'wa_inbound_messages.received_at');
        })->count();

        return [
            Stat::make($user?->isAgent() ? 'Mis leads' : 'Leads activos', $contactable)
                ->description('Habilitados para contactar')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Para hoy', $followUpsToday)
                ->description('Seguimientos de hoy')
                ->icon('heroicon-o-calendar-days')
                ->color($followUpsToday > 0 ? 'warning' : 'gray'),

            Stat::make('Vencidos', $overdueFollowUps)
                ->description($overdueFollowUps > 0 ? 'Requieren atención urgente' : 'Todo al día')
                ->icon($overdueFollowUps > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overdueFollowUps > 0 ? 'danger' : 'success'),

            Stat::make('Sin responder', $unreplied)
                ->description('WhatsApp entrantes (48h)')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color($unreplied > 0 ? 'warning' : 'success')
                ->url('/davyt/inbox'),

            Stat::make('Enviados esta semana', $sentThisWeek)
                ->description('Mensajes de WhatsApp')
                ->icon('heroicon-o-paper-airplane')
                ->color('success'),
        ];
    }
}
