<?php

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\ScheduledMessage;
use App\Models\User;
use App\Services\MessageSender;
use App\Services\WhatsAppService;
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

        // Process agent report steps (send_report)
        $reports = ScheduledMessage::query()
            ->with(['lead.user', 'lead.primaryListing', 'lead.leadStatus', 'lead.company'])
            ->where('channel', 'agent_report')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($maxPerRun)
            ->get();

        foreach ($reports as $report) {
            $lead     = $report->lead;
            $company  = $lead?->company;
            $stepData = $report->step_data ?? [];

            if (! $company || ! $company->wa_active || ! $company->wa_phone_number_id || ! $company->wa_access_token) {
                continue;
            }

            try {
                // Optional agent reassignment before sending
                if (! empty($stepData['agent_id'])) {
                    $lead->updateQuietly(['user_id' => $stepData['agent_id']]);
                    $lead->refresh();
                }

                $agent      = $lead->user;
                $agentPhone = preg_replace('/\D/', '', $agent?->phone ?? '');

                if (! $agentPhone) {
                    $report->update(['status' => 'cancelled', 'error_message' => 'El agente no tiene teléfono registrado.']);
                    $this->warn("  ⚠ Informe Lead #{$lead->id}: agente sin teléfono.");
                    continue;
                }

                $body = $this->buildReport($lead);

                app(WhatsAppService::class)->sendTextMessage($company, $agentPhone, $body);

                $report->update(['status' => 'sent', 'sent_at' => now(), 'message_body' => $body]);

                Activity::log(
                    event: 'agent_report_sent',
                    description: "Ficha de cliente enviada al agente {$agent->name} por WhatsApp.",
                    subject: $lead,
                    properties: ['agent_id' => $agent->id],
                );

                $this->line("  ✓ Informe Lead #{$lead->id} → {$agent->name}");
            } catch (\Throwable $e) {
                $report->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                $this->error("  ✗ Informe Lead #{$lead->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function buildReport(Lead $lead): string
    {
        $listing = $lead->primaryListing;
        $status  = $lead->leadStatus?->name ?? 'Sin estado';
        $agent   = $lead->user?->name ?? 'Sin asignar';

        $lines   = [];
        $lines[] = '🏠 *FICHA DE CLIENTE*';
        $lines[] = '';
        $lines[] = '👤 *' . ($lead->name ?? 'Sin nombre') . '*';
        $lines[] = '📞 ' . ($lead->phone ?? 'Sin teléfono');

        if ($lead->email) {
            $lines[] = '📧 ' . $lead->email;
        }

        if ($listing) {
            $lines[] = '';
            $lines[] = '🏘 *Propiedad*';

            $tipo = implode(' · ', array_filter([
                $listing->property_type ? ucfirst($listing->property_type) : null,
                $listing->operation ? 'en ' . $listing->operation : null,
            ]));

            if ($tipo) $lines[] = '• ' . $tipo;
            if ($listing->zone_raw ?? $lead->zone) $lines[] = '📍 ' . ($listing->zone_raw ?? $lead->zone);

            if ($listing->asking_price) {
                $currency = strtoupper($listing->price_currency ?? 'USD');
                $lines[] = '💰 ' . $currency . ' ' . number_format($listing->asking_price, 0, ',', '.');
            }

            $specs = array_filter([
                $listing->bedrooms  ? $listing->bedrooms . ' dorm'  : null,
                $listing->bathrooms ? $listing->bathrooms . ' baños' : null,
                $listing->m2_covered ? $listing->m2_covered . ' m²'  : null,
            ]);
            if ($specs) $lines[] = '📐 ' . implode(' · ', $specs);

            if ($listing->listing_url) {
                $lines[] = '🔗 ' . $listing->listing_url;
            }
        } elseif ($lead->zone || $lead->property_type) {
            $lines[] = '';
            $lines[] = '🔎 *Interés*';
            if ($lead->property_type) $lines[] = '• ' . ucfirst($lead->property_type);
            if ($lead->zone)          $lines[] = '📍 ' . $lead->zone;
        }

        if ($lead->notes) {
            $lines[] = '';
            $lines[] = '📝 *Notas:* ' . $lead->notes;
        }

        $lines[] = '';
        $lines[] = '📊 Estado: ' . $status;
        if ($lead->source) $lines[] = '🌐 Origen: ' . $lead->source;
        $lines[] = '🗓 Ingresó: ' . ($lead->created_at?->format('d/m/Y') ?? '-');
        $lines[] = '👤 Agente: ' . $agent;

        return implode("\n", $lines);
    }
}
