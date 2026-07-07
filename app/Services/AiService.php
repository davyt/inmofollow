<?php

namespace App\Services;

use App\Models\AiAgent;
use App\Models\Company;
use App\Models\Lead;
use App\Models\WaInboundMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiService
{
    public function generateTemplateBody(string $channel, string $description): string
    {
        $company = Company::find(config('inmofollow.default_company_id', 1));
        $apiKey  = $company?->anthropic_api_key ?: config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('La función de IA no está activada. Configurá la API Key de Anthropic en Configuración → Mi empresa.');
        }

        $channelLabel = match ($channel) {
            'whatsapp' => 'WhatsApp',
            'email'    => 'correo electrónico',
            default    => $channel,
        };

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
            'max_tokens' => 1024,
            'system'     => $this->systemPrompt(),
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => "Canal: {$channelLabel}\n\nDescripción: {$description}",
                ],
            ],
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? 'Error desconocido';
            throw new RuntimeException("Error de la API de IA: {$error}");
        }

        return trim($response->json('content.0.text') ?? '');
    }

    public function generateReply(Lead $lead, string $inboundMessage, AiAgent $agent): string
    {
        $company = Company::find($lead->company_id);
        $apiKey  = $company?->anthropic_api_key ?: config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('API Key de Anthropic no configurada.');
        }

        $history = $this->buildHistory($lead);

        $contextLines = [
            'Nombre del lead: ' . ($lead->name ?? 'Desconocido'),
            'Zona de interés: ' . ($lead->zone ?? 'no especificada'),
            'Tipo de propiedad: ' . ($lead->property_type ?? 'no especificado'),
        ];

        $systemContent = $agent->system_prompt . "\n\nContexto del lead:\n" . implode("\n", $contextLines);

        $messages   = $history->toArray();
        $messages[] = ['role' => 'user', 'content' => $inboundMessage];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
            'max_tokens' => 512,
            'system'     => $systemContent,
            'messages'   => $messages,
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? 'Error desconocido';
            throw new RuntimeException("Error de la API de IA: {$error}");
        }

        return trim($response->json('content.0.text') ?? '');
    }

    private function buildHistory(Lead $lead): Collection
    {
        $sent = $lead->scheduledMessages()
            ->where('channel', 'whatsapp')
            ->where('status', 'sent')
            ->orderBy('sent_at')
            ->limit(10)
            ->get()
            ->map(fn ($m) => ['role' => 'assistant', 'content' => $m->message_body ?? '', 'at' => $m->sent_at]);

        $received = WaInboundMessage::where('lead_id', $lead->id)
            ->orderBy('received_at')
            ->limit(10)
            ->get()
            ->map(fn ($m) => ['role' => 'user', 'content' => $m->body ?? '', 'at' => $m->received_at]);

        return $sent->concat($received)
            ->sortBy('at')
            ->takeLast(10)
            ->map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->values();
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Sos un asistente especializado en comunicación inmobiliaria. Tu tarea es generar mensajes profesionales y cálidos para agentes inmobiliarios uruguayos.

Reglas:
- Usá tuteo (vos/te) y lenguaje natural, sin ser demasiado formal ni informal
- Sé conciso y directo para WhatsApp (máximo 3-4 párrafos cortos)
- Para email podés ser más elaborado
- Incluí las variables disponibles cuando corresponda: {{nombre}}, {{zona}}, {{tipo_propiedad}}, {{agente}}
- Para WhatsApp no uses saludos finales largos; para email sí (ej: "Saludos cordiales, {{agente}}")
- El tono debe ser profesional pero amigable, orientado al mercado uruguayo
- Respondé SOLO con el texto del mensaje, sin explicaciones adicionales, sin comillas
PROMPT;
    }
}
