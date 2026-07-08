<?php

namespace App\Services;

use App\Models\AiAgent;
use App\Models\AiKnowledgeEntry;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Sequence;
use App\Models\User;
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

        $channelLabel = match ($channel) {
            'whatsapp' => 'WhatsApp',
            'email'    => 'correo electrónico',
            default    => $channel,
        };

        $messages = [['role' => 'user', 'content' => "Canal: {$channelLabel}\n\nDescripción: {$description}"]];
        $system   = $this->templateSystemPrompt();

        // Prefer the configured AiAgent (any provider) over the Anthropic-only key
        $agent = AiAgent::where('company_id', $company?->id ?? 1)->first();

        if ($agent && ! empty($agent->api_key)) {
            $model = $agent->model ?: $this->defaultModel($agent->provider);
            return match ($agent->provider) {
                'anthropic'  => $this->callAnthropic($agent->api_key, $model, $system, $messages, 1024),
                'openai'     => $this->callOpenAiCompat($agent->api_key, 'https://api.openai.com/v1/chat/completions', $model, $system, $messages),
                'groq'       => $this->callOpenAiCompat($agent->api_key, 'https://api.groq.com/openai/v1/chat/completions', $model, $system, $messages),
                'gemini'     => $this->callGemini($agent->api_key, $model, $system, $messages),
                'openrouter' => $this->callOpenAiCompat($agent->api_key, 'https://openrouter.ai/api/v1/chat/completions', $model, $system, $messages),
                default      => throw new RuntimeException("Proveedor no soportado: {$agent->provider}"),
            };
        }

        // Fallback: company Anthropic key
        $apiKey = $company?->anthropic_api_key ?: config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('Configurá una API Key en Agentes IA (o la clave de Anthropic en Mi empresa) para usar esta función.');
        }

        return $this->callAnthropic(
            apiKey:    $apiKey,
            model:     config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
            system:    $system,
            messages:  $messages,
            maxTokens: 1024,
        );
    }

    // -------------------------------------------------------------------------
    // AI agent reply generation (multi-provider)
    // -------------------------------------------------------------------------

    /**
     * Generate a reply for the playground (conversation history passed directly, no DB writes).
     * Returns ['reply' => string, 'actions' => [...]]
     */
    public function generatePlaygroundReply(array $history, string $inboundMessage, AiAgent $agent, ?Lead $lead, int $companyId): array
    {
        $apiKey = $this->resolveApiKeyByCompany($agent, $companyId);

        $messages = array_merge($history, [['role' => 'user', 'content' => $inboundMessage]]);

        $contextLines = $lead ? array_filter([
            'Nombre del lead: '   . ($lead->name ?? 'Desconocido'),
            'Zona de interés: '   . ($lead->zone ?? null),
            'Tipo de propiedad: ' . ($lead->property_type ?? null),
        ]) : ['(Modo prueba — sin lead real)'];

        $system = $agent->system_prompt . "\n\nContexto del lead:\n" . implode("\n", $contextLines);

        $kb = $this->buildKnowledgeContext($companyId);
        if ($kb) {
            $system .= "\n\n---\nBASE DE CONOCIMIENTO:\n" . $kb;
        }

        $system .= "\n\n" . $this->buildActionsContext($companyId);

        $model = $agent->model ?: $this->defaultModel($agent->provider);

        $raw = match ($agent->provider) {
            'anthropic'  => $this->callAnthropic($apiKey, $model, $system, $messages, 600),
            'openai'     => $this->callOpenAiCompat($apiKey, 'https://api.openai.com/v1/chat/completions', $model, $system, $messages),
            'groq'       => $this->callOpenAiCompat($apiKey, 'https://api.groq.com/openai/v1/chat/completions', $model, $system, $messages),
            'gemini'     => $this->callGemini($apiKey, $model, $system, $messages),
            'openrouter' => $this->callOpenAiCompat($apiKey, 'https://openrouter.ai/api/v1/chat/completions', $model, $system, $messages),
            default      => throw new RuntimeException("Proveedor no soportado: {$agent->provider}"),
        };

        return $this->parseActionsFromReply($raw);
    }

    /**
     * Generate an AI reply for an inbound WhatsApp message.
     * Returns ['reply' => string, 'actions' => [['type' => string, 'value' => int], ...]]
     */
    public function generateReply(Lead $lead, string $inboundMessage, AiAgent $agent): array
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

        $system = $agent->system_prompt . "\n\nContexto del lead:\n" . implode("\n", $contextLines);

        $kb = $this->buildKnowledgeContext($lead->company_id);
        if ($kb) {
            $system .= "\n\n---\nBASE DE CONOCIMIENTO:\n" . $kb;
        }

        $system .= "\n\n" . $this->buildActionsContext($lead->company_id);

        $model = $agent->model ?: $this->defaultModel($agent->provider);

        $raw = match ($agent->provider) {
            'anthropic'  => $this->callAnthropic($apiKey, $model, $system, $messages, 600),
            'openai'     => $this->callOpenAiCompat($apiKey, 'https://api.openai.com/v1/chat/completions', $model, $system, $messages),
            'groq'       => $this->callOpenAiCompat($apiKey, 'https://api.groq.com/openai/v1/chat/completions', $model, $system, $messages),
            'gemini'     => $this->callGemini($apiKey, $model, $system, $messages),
            'openrouter' => $this->callOpenAiCompat($apiKey, 'https://openrouter.ai/api/v1/chat/completions', $model, $system, $messages),
            default      => throw new RuntimeException("Proveedor no soportado: {$agent->provider}"),
        };

        return $this->parseActionsFromReply($raw);
    }

    /**
     * Parse [ESTADO:N], [AGENTE:N], [SECUENCIA:N] from AI response.
     * Returns cleaned reply and list of actions to execute.
     */
    public function parseActionsFromReply(string $raw): array
    {
        $actions = [];

        preg_match_all('/\[(ESTADO|AGENTE|SECUENCIA):(\d+)\]/i', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $actions[] = [
                'type'  => match (strtoupper($match[1])) {
                    'ESTADO'    => 'update_status',
                    'AGENTE'    => 'assign_agent',
                    'SECUENCIA' => 'trigger_sequence',
                },
                'value' => (int) $match[2],
            ];
        }

        $reply = trim(preg_replace('/\[(ESTADO|AGENTE|SECUENCIA):\d+\]/i', '', $raw));

        return ['reply' => $reply, 'actions' => $actions];
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
        return $this->resolveApiKeyByCompany($agent, $lead->company_id);
    }

    private function resolveApiKeyByCompany(AiAgent $agent, int $companyId): string
    {
        if (! empty($agent->api_key)) {
            return $agent->api_key;
        }

        if ($agent->provider === 'anthropic') {
            $company = Company::find($companyId);
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
            ->take(-10)
            ->map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->values();
    }

    private function buildKnowledgeContext(int $companyId): string
    {
        $entries = AiKnowledgeEntry::where('company_id', $companyId)
            ->where('active', true)
            ->get(['title', 'content']);

        if ($entries->isEmpty()) return '';

        $parts  = [];
        $budget = 6000; // chars max para no inflar el context

        foreach ($entries as $entry) {
            $chunk   = "### {$entry->title}\n" . mb_substr($entry->content, 0, 2000);
            $budget -= mb_strlen($chunk);
            $parts[] = $chunk;
            if ($budget <= 0) break;
        }

        return implode("\n\n", $parts);
    }

    private function buildActionsContext(int $companyId): string
    {
        $statuses = LeadStatus::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn ($s) => "{$s->id}={$s->name}")
            ->implode(', ');

        $agents = User::where('company_id', $companyId)
            ->where('active', true)
            ->whereIn('role', ['agent', 'supervisor'])
            ->get(['id', 'name'])
            ->map(fn ($u) => "{$u->id}={$u->name}")
            ->implode(', ');

        $sequences = Sequence::where('company_id', $companyId)
            ->where('active', true)
            ->get(['id', 'name'])
            ->map(fn ($s) => "{$s->id}={$s->name}")
            ->implode(', ');

        $lines = [
            '---',
            'ACCIONES DISPONIBLES (opcionales — solo usá las que sean claramente apropiadas):',
            'Podés incluir comandos al final de tu respuesta. Se ejecutan automáticamente y el cliente NO los ve.',
            '',
            'Comandos:',
            '  [ESTADO:N]    → cambia el estado del lead al ID N',
            '  [AGENTE:N]    → asigna al agente con ID N',
            '  [SECUENCIA:N] → dispara el flow con ID N',
            '',
            "Estados: {$statuses}",
            "Agentes: {$agents}",
            "Flows: {$sequences}",
            '',
            'Ejemplo: "Gracias! Te paso con un agente.[ESTADO:3][AGENTE:2]"',
            'IMPORTANTE: Solo usá comandos si el contexto lo justifica claramente.',
        ];

        return implode("\n", $lines);
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
