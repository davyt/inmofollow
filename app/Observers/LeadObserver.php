<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\User;
use App\Services\FollowUpGenerator;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        app(FollowUpGenerator::class)->generateForLead($lead, 'lead_created');
    }

    public function updated(Lead $lead): void
    {
        if ($lead->isDirty('lead_status_id') && $lead->lead_status_id !== null) {
            app(FollowUpGenerator::class)->generateForLead($lead, 'status_change');
            $this->notifyIfInterested($lead);
        }
    }

    private function notifyIfInterested(Lead $lead): void
    {
        $status = $lead->leadStatus;

        if (! $status || mb_stripos($status->name, 'interes') === false) {
            return;
        }

        $label   = $lead->name ?? $lead->phone ?? "Lead #{$lead->id}";
        $zone    = $lead->zone ? " · {$lead->zone}" : '';
        $type    = $lead->property_type ? " · {$lead->property_type}" : '';
        $summary = $lead->ai_classification ? " — \"{$lead->ai_classification}\"" : '';

        $notification = Notification::make()
            ->title("🔥 Lead interesado: {$label}")
            ->body("Respondió con interés{$zone}{$type}{$summary}")
            ->icon('heroicon-o-fire')
            ->actions([
                FilamentAction::make('ver')->label('Abrir lead')->url('/davyt/leads/' . $lead->id . '/edit'),
            ])
            ->toDatabase();

        // Notificar al agente asignado + admins/supervisores de la empresa
        User::where('company_id', $lead->company_id)
            ->where(fn ($q) => $q
                ->where('role', '!=', 'agent')
                ->orWhere('id', $lead->user_id)
            )
            ->get()
            ->each(fn ($user) => $user->notifyNow($notification));
    }
}
