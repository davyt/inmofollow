<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\User;
use App\Models\WaInboundMessage;
use App\Models\AiAgent;
use App\Services\AiService;
use App\Services\FollowUpGenerator;
use App\Models\ScheduledMessage as ScheduledMsg;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! $this->signatureIsValid($request)) {
            Log::warning('WA webhook signature invalid');
            return response('Unauthorized', 401);
        }

        foreach (data_get($request->all(), 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    $this->handleStatusUpdate($status);
                }

                foreach (data_get($change, 'value.messages', []) as $message) {
                    $this->handleInboundMessage($message);
                }
            }
        }

        return response('OK', 200);
    }

    private function signatureIsValid(Request $request): bool
    {
        $secret = config('services.whatsapp.app_secret');

        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function handleStatusUpdate(array $status): void
    {
        $waMessageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;

        if (! $waMessageId || ! $statusValue) {
            return;
        }

        $message = ScheduledMessage::where('wa_message_id', $waMessageId)->first();

        if (! $message) {
            return;
        }

        if ($statusValue === 'failed') {
            $message->update([
                'status'        => 'failed',
                'error_message' => data_get($status, 'errors.0.title', 'Error desconocido'),
            ]);
        }
    }

    private function handleInboundMessage(array $message): void
    {
        $from = $message['from'] ?? null;

        if (! $from) {
            return;
        }

        $lead = Lead::findByWhatsAppPhone($from);

        if (! $lead) {
            Log::warning('WA webhook: no lead matched inbound phone', ['from' => $from]);
            return;
        }

        // Actualizar sesión activa: el lead nos escribió, abre ventana de 24hs
        $lead->update(['last_wa_inbound_at' => now()]);

        $type = $message['type'] ?? 'unknown';
        $body = match ($type) {
            'text'        => data_get($message, 'text.body'),
            'button'      => data_get($message, 'button.text'),
            'interactive' => data_get($message, 'interactive.button_reply.title') ?? data_get($message, 'interactive.list_reply.title'),
            default       => data_get($message, "{$type}.caption"),
        };

        $inboundMsg = WaInboundMessage::create([
            'lead_id'       => $lead->id,
            'company_id'    => $lead->company_id,
            'wa_message_id' => $message['id'] ?? null,
            'message_type'  => $type,
            'body'          => $body,
            'received_at'   => isset($message['timestamp']) ? Carbon::createFromTimestamp((int) $message['timestamp']) : now(),
        ]);

        $this->notifyInboundMessage($lead, $body, $type);
        $this->triggerAiAgent($lead, $inboundMsg, $body);
    }

    private function triggerAiAgent(Lead $lead, WaInboundMessage $msg, ?string $body): void
    {
        if (! $body) return;

        $agent = AiAgent::where('company_id', $lead->company_id)
            ->where('active', true)
            ->first();

        if (! $agent) return;

        try {
            $result  = app(AiService::class)->generateReply($lead, $body, $agent);
            $reply   = $result['reply'];
            $actions = $result['actions'];


            if ($agent->auto_send) {
                $company = $lead->company;
                if ($company?->wa_active && $company->wa_phone_number_id && $company->wa_access_token) {
                    $waId = app(\App\Services\WhatsAppService::class)
                        ->sendTextMessage($company, $lead->phone, $reply);

                    ScheduledMsg::create([
                        'lead_id'       => $lead->id,
                        'channel'       => 'whatsapp',
                        'message_body'  => $reply,
                        'status'        => 'sent',
                        'scheduled_for' => now(),
                        'sent_at'       => now(),
                        'wa_message_id' => $waId,
                    ]);

                    $lead->update(['last_contacted_at' => now()]);
                }
            } else {
                $msg->update(['ai_draft_reply' => $reply]);
            }

            // Execute AI-requested actions and collect summaries for notification
            $executedSummaries = [];
            foreach ($actions as $action) {
                $summary = $this->executeAiAction($lead->fresh(), $action);
                if ($summary) {
                    $executedSummaries[] = $summary;
                }
            }

            if (! empty($executedSummaries)) {
                $this->notifyAiActions($lead->fresh(), $executedSummaries);
            }
        } catch (\Throwable $e) {
            Log::warning('AI agent error: ' . $e->getMessage(), ['lead_id' => $lead->id]);
        }
    }

    private function executeAiAction(Lead $lead, array $action): ?string
    {
        try {
            return match ($action['type']) {
                'update_status'    => $this->aiUpdateStatus($lead, (int) $action['value']),
                'assign_agent'     => $this->aiAssignAgent($lead, (int) $action['value']),
                'trigger_sequence' => $this->aiTriggerSequence($lead),
                'classify_lead'    => $this->aiClassifyLead($lead, (string) $action['value']),
                default            => null,
            };
        } catch (\Throwable $e) {
            Log::warning("AI action '{$action['type']}' failed: " . $e->getMessage(), ['lead_id' => $lead->id]);
            return null;
        }
    }

    private function aiUpdateStatus(Lead $lead, int $statusId): ?string
    {
        $status = \App\Models\LeadStatus::find($statusId);
        if (! $status) return null;

        // update() (not updateQuietly) so LeadObserver fires and triggers matching flows
        $lead->update(['lead_status_id' => $statusId]);

        return "Estado → {$status->name}";
    }

    private function aiAssignAgent(Lead $lead, int $agentId): ?string
    {
        $agent = User::find($agentId);
        if (! $agent) return null;

        $lead->update(['user_id' => $agentId]);

        return "Asignado → {$agent->name}";
    }

    private function aiTriggerSequence(Lead $lead): ?string
    {
        $created = app(FollowUpGenerator::class)->generateForLead($lead, 'status_change');
        return $created > 0 ? "Flow disparado ({$created} paso(s) programado(s))" : null;
    }

    private function aiClassifyLead(Lead $lead, string $classification): ?string
    {
        // updateQuietly to avoid re-firing LeadObserver / flows
        $lead->updateQuietly([
            'ai_classification' => $classification,
            'ai_classified_at'  => now(),
        ]);

        return "Clasificado → {$classification}";
    }

    private function notifyAiActions(Lead $lead, array $summaries): void
    {
        $body = implode(' · ', $summaries);

        $dbNotification = Notification::make()
            ->title('🤖 IA actuó sobre ' . ($lead->name ?? 'lead'))
            ->body($body)
            ->icon('heroicon-o-cpu-chip')
            ->actions([
                FilamentAction::make('ver')->label('Ver lead')->url('/davyt/inbox'),
            ])
            ->toDatabase();

        $recipients = User::where('company_id', $lead->company_id)
            ->where(fn ($q) => $q
                ->where('role', '!=', 'agent')
                ->orWhere('id', $lead->user_id)
            )
            ->get();

        foreach ($recipients as $user) {
            $user->notifyNow($dbNotification);
        }
    }

    private function notifyInboundMessage(Lead $lead, ?string $body, string $type): void
    {
        $preview = $body ? Str::limit($body, 80) : ucfirst($type);

        $dbNotification = Notification::make()
            ->title('💬 ' . ($lead->name ?? 'Lead'))
            ->body($preview)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->actions([
                FilamentAction::make('ver')->label('Ver conversación')->url('/davyt/inbox'),
            ])
            ->toDatabase();

        $recipients = User::where('company_id', $lead->company_id)
            ->where(fn ($q) => $q
                ->where('role', '!=', 'agent')
                ->orWhere('id', $lead->user_id)
            )
            ->get();

        foreach ($recipients as $user) {
            $user->notifyNow($dbNotification);
        }
    }
}
