<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyFollowUps extends Command
{
    protected $signature   = 'notify:follow-ups';
    protected $description = 'Notifica a los usuarios sobre seguimientos programados para hoy';

    public function handle(): void
    {
        $leads = Lead::with('user', 'leadStatus')
            ->whereDate('next_follow_up_at', today())
            ->where('do_not_contact', false)
            ->get();

        foreach ($leads as $lead) {
            $recipients = User::where('company_id', $lead->company_id)
                ->where(fn ($q) => $q
                    ->where('role', '!=', 'agent')
                    ->orWhere('id', $lead->user_id)
                )
                ->get();

            $dbNotification = Notification::make()
                ->title('📅 Seguimiento: ' . ($lead->name ?? 'Lead'))
                ->body('Seguimiento programado para hoy' . ($lead->leadStatus ? ' · ' . $lead->leadStatus->name : ''))
                ->icon('heroicon-o-clock')
                ->toDatabase();

            foreach ($recipients as $user) {
                $user->notifyNow($dbNotification);
            }
        }

        $this->info("Notificaciones enviadas: {$leads->count()} seguimientos.");
    }
}
