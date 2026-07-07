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
    // -------------------------------------------------------------------------
    // Template body generation (existing feature, uses company Anthropic key)
    // -------------------------------------------------------------------------

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

        return $this->callAnthropic(
            apiKey:    $apiKey,
            model:     config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
            system:    $this->templateSystemPrompt(),
            messages:  [['role' => 'user', 'content' => "Canal: {$channelLabel}\n\nDescripción: {$description}"]],
            maxTokens: 1024,
        );
    }

    // -------------------------------------------------------------------------
    // AI agent reply generation (multi-provider)
    // -------------------------------------------------------------------------

    public function generateReply(Lead $lead, string $inboundMessage, AiAgent $agent): string
    {
        $apiKey = $this->resolveApiKey($agent, $lead);

        $history  = $this->buildHistory($lead);
        $messages = $history->toArray();
        $messages[] = ['role' => 'user', 'content' => $inboundMessage];

        $contextLines = array_filter([
            'Nombre del lead: ' . ($lead->name ?? 'Desconocido'),
            'Zona de interés: '  . ($lead->zone ?? null),
            'Tipo de propiedad: '. ($lead->property_type ?? null),
        ]);

        $system = $agent->system_prompt . "\n\nContexto:\n" . implode("\n", $contextLines);
        $model  = $agent->model ?: $this->defaultModel($agent->provider);

        return match ($agent->provider) {
            'anthropic'  => $this->callAnthropic($apiKey, $model, $system, $messages, 512),
            'openai'     => $this->callOpenAiCompat($apiKey, 'https://api.openai.com/v1/chat/completions', $model, $system, $messages),
            'groq'       => $this->callOpenAiCompat($apiKey, 'https://api.groq.com/openai/v1/chat/completions', $model, $system, $messages),
            'gemini'     => $this->callGemini($apiKey, $model, $system, $messages),
            'openrouter' => $this->callOpenAiCompat($apiKey, 'https://openrouter.ai/api/v1/chat/completions', $model, $system, $messages),
            default      => throw new RuntimeException("Proveedor no soportado: {$agent->provider}"),
        };
    }

    // -------------------------------------------------------------------------
    // Provider implementations
    // -------------------------------------------------------------------------

    private function callAnthropic(string $apiKey, string $model, string $system, array $messages, int $maxTokens = 512): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic: ' . ($response->json('error.message') ?? $response->status()));
        }

        return trim($response->json('content.0.text') ?? '');
    }

    private function callOpenAiCompat(string $apiKey, string $endpoint, string $model, string $system, array $messages): string
    {
        $payload = array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages,
        );

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post($endpoint, [
                'model'      => $model,
                'messages'   => $payload,
                'max_tokens' => 512,
            ]);

        if ($response->failed()) {
            $err = $response->json('error.message') ?? $response->json('error') ?? $response->status();
            throw new RuntimeException("API error: {$err}");
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    private function callGemini(string $apiKey, string $model, string $system, array $messages): string
    {
        $contents = [];
        foreach ($messages as $m) {
            $contents[] = [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ];
        }

        $response = Http::timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents'           => $contents,
                'generationConfig'   => ['maxOutputTokens' => 512],
            ]);

        if ($response->failed()) {
            $err = $response->json('error.message') ?? $response->status();
            throw new RuntimeException("Gemini: {$err}");
        }

        return trim($response->json('candidates.0.content.parts.0.text') ?? '');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveApiKey(AiAgent $agent, Lead $lead): string
    {
        if (! empty($agent->api_key)) {
            return $agent->api_key;
        }

        // Fallback a clave de la compañía (solo para Anthropic)
        if ($agent->provider === 'anthropic') {
            $company = Company::find($lead->company_id);
            $key     = $company?->anthropic_api_key ?: config('services.anthropic.api_key');
            if ($key) return $key;
        }

        throw new RuntimeException("No hay API Key configurada para el agente IA ({$agent->provider}).");
    }

    private function defaultModel(string $provider): string
    {
        return match ($provider) {
            'anthropic'  => 'claude-haiku-4-5-20251001',
            'openai'     => 'gpt-4o-mini',
            'groq'       => 'llama-3.1-8b-instant',
            'gemini'     => 'gemini-1.5-flash',
            'openrouter' => 'meta-llama/llama-3.1-8b-instruct:free',
            default      => '',
        };
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

    private function templateSystemPrompt(): string
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
