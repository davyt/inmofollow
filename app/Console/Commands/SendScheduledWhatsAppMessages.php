<?php

namespace App\Console\Commands;

use App\Models\ScheduledMessage;
use App\Services\WhatsAppService;
use App\Support\Activity;
use Illuminate\Console\Command;

class SendScheduledWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:send-scheduled';
    protected $description = 'Envía los mensajes de WhatsApp programados que ya deben enviarse';

    public function handle(WhatsAppService $service): int
    {
        $messages = ScheduledMessage::query()
            ->with(['lead.company', 'lead'])
            ->where('channel', 'whatsapp')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->get();

        if ($messages->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Procesando {$messages->count()} mensaje(s) pendiente(s)...");

        foreach ($messages as $message) {
            $lead    = $message->lead;
            $company = $lead?->company;

            if (! $company || ! $company->wa_active || ! $company->wa_phone_number_id || ! $company->wa_access_token) {
                continue;
            }

            if (! $lead->phone || $lead->do_not_contact) {
                $message->update(['status' => 'cancelled']);
                continue;
            }

            try {
                $waId = $service->sendTextMessage($company, $lead->phone, $message->message_body);

                $message->update([
                    'status'        => 'sent',
                    'sent_at'       => now(),
                    'wa_message_id' => $waId,
                ]);

                $lead->update(['last_contacted_at' => now()]);

                Activity::log(
                    event: 'whatsapp_sent_auto',
                    description: 'WhatsApp enviado automáticamente por el sistema.',
                    subject: $message,
                    properties: ['lead_id' => $lead->id, 'wa_message_id' => $waId],
                );

                $this->line("  ✓ Lead #{$lead->id} ({$lead->name})");
            } catch (\Throwable $e) {
                $message->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Activity::log(
                    event: 'whatsapp_failed',
                    description: 'Error al enviar WhatsApp: ' . $e->getMessage(),
                    subject: $message,
                    properties: ['lead_id' => $lead->id],
                );

                $this->error("  ✗ Lead #{$lead->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
