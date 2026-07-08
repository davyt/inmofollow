<?php

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Services\MessageSender;
use App\Support\Activity;
use Illuminate\Console\Command;

class SendScheduledWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:send-scheduled';
    protected $description = 'Envía los mensajes de WhatsApp programados que ya deben enviarse';

    public function handle(MessageSender $sender): int
    {
        $maxPerRun = (int) config('inmofollow.whatsapp_max_sends_per_run', 20);
        $delayMs   = (int) config('inmofollow.whatsapp_send_delay_ms', 500);

        $messages = ScheduledMessage::query()
            ->with(['lead.company', 'lead', 'messageTemplate', 'user'])
            ->where('channel', 'whatsapp')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($maxPerRun)
            ->get();

        if ($messages->isEmpty()) {
            $this->line('Sin mensajes pendientes.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$messages->count()} mensaje(s) pendiente(s) (máx. {$maxPerRun} por corrida)...");

        $isFirst = true;

        foreach ($messages as $message) {
            if (! $isFirst && $delayMs > 0) {
                usleep($delayMs * 1000);
            }
            $isFirst = false;

            $lead     = $message->lead;
            $company  = $lead?->company;
            $template = $message->messageTemplate;

            if (! $company || ! $company->wa_active || ! $company->wa_phone_number_id || ! $company->wa_access_token) {
                continue;
            }

            if (! $lead->phone || $lead->do_not_contact || ! $lead->whatsapp_consent) {
                $message->update(['status' => 'cancelled']);
                continue;
            }

            try {
                if ($template) {
                    $waId = $sender->send($lead, $template, $company, $message->user);
                    $body = $sender->substituteVariables($template->body, $lead, $message->user);
                } else {
                    // Sin plantilla, usar el body guardado directamente
                    $waId = app(\App\Services\WhatsAppService::class)
                        ->sendTextMessage($company, $lead->phone, $message->message_body);
                    $body = $message->message_body;
                }

                $message->update([
                    'status'        => 'sent',
                    'sent_at'       => now(),
                    'wa_message_id' => $waId,
                    'message_body'  => $body,
                ]);

                $lead->update(['last_contacted_at' => now()]);

                if ($message->broadcast_id) {
                    Broadcast::where('id', $message->broadcast_id)->increment('sent_count');
                }

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

                if ($message->broadcast_id) {
                    Broadcast::where('id', $message->broadcast_id)->increment('failed_count');
                }

                Activity::log(
                    event: 'whatsapp_failed',
                    description: 'Error al enviar WhatsApp: ' . $e->getMessage(),
                    subject: $message,
                    properties: ['lead_id' => $lead->id],
                );

                $this->error("  ✗ Lead #{$lead->id}: " . $e->getMessage());
            }
        }

        // Process scheduled action steps (update_status, assign_agent)
        $actions = ScheduledMessage::query()
            ->with('lead')
            ->where('channel', 'action')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($maxPerRun)
            ->get();

        foreach ($actions as $action) {
            $lead     = $action->lead;
            $stepData = $action->step_data ?? [];

            try {
                match ($action->step_type) {
                    'update_status' => Lead::where('id', $lead->id)->update(['lead_status_id' => $stepData['status_id'] ?? null]),
                    'assign_agent'  => Lead::where('id', $lead->id)->update(['user_id' => $stepData['agent_id'] ?? null]),
                    default         => null,
                };

                $action->update(['status' => 'sent', 'sent_at' => now()]);
                $this->line("  ✓ Acción {$action->step_type} → Lead #{$lead?->id}");
            } catch (\Throwable $e) {
                $action->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                $this->error("  ✗ Acción fallida Lead #{$lead?->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
