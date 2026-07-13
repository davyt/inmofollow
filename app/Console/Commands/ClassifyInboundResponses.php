<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\Lead;
use App\Models\WaInboundMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClassifyInboundResponses extends Command
{
    protected $signature   = 'leads:classify-responses {--all : Reclasificar aunque ya tengan clasificación}';
    protected $description = 'Clasifica leads según sus mensajes entrantes de WhatsApp usando IA';

    public function handle(): int
    {
        $agent = AiAgent::where('active', true)->first();

        if (! $agent || empty($agent->api_key)) {
            $this->error('No hay agente IA activo con API key configurada.');
            return 1;
        }

        $reclassify = $this->option('all');

        // Leads con al menos un mensaje entrante
        $leadIds = WaInboundMessage::whereNotNull('body')
            ->where('body', '!=', '')
            ->distinct()
            ->pluck('lead_id');

        $query = Lead::whereIn('id', $leadIds);

        if (! $reclassify) {
            $query->whereNull('ai_classification');
        }

        $leads = $query->get();

        if ($leads->isEmpty()) {
            $this->info('No hay leads pendientes de clasificar.');
            return 0;
        }

        $this->info("Clasificando {$leads->count()} leads...");
        $bar = $this->output->createProgressBar($leads->count());
        $bar->start();

        $ok = 0;
        $errors = 0;

        foreach ($leads as $lead) {
            try {
                $messages = WaInboundMessage::where('lead_id', $lead->id)
                    ->whereNotNull('body')
                    ->orderBy('received_at')
                    ->pluck('body')
                    ->map(fn ($b) => trim($b))
                    ->filter()
                    ->values();

                if ($messages->isEmpty()) {
                    $bar->advance();
                    continue;
                }

                $classification = $this->classify($messages->all(), $agent);

                if ($classification) {
                    $lead->update([
                        'ai_classification'  => $classification,
                        'ai_classified_at'   => now(),
                    ]);
                    $ok++;
                }
            } catch (\Throwable $e) {
                Log::warning("classify-responses lead {$lead->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
            usleep(300_000); // 300ms entre llamadas para no saturar la API
        }

        $bar->finish();
        $this->newLine();
        $this->info("Clasificados: {$ok} | Errores: {$errors}");

        return 0;
    }

    private function classify(array $messages, AiAgent $agent): ?string
    {
        $conversation = implode("\n", array_map(
            fn ($i, $m) => 'Mensaje ' . ($i + 1) . ': ' . mb_substr($m, 0, 300),
            array_keys($messages),
            $messages
        ));

        $prompt = <<<PROMPT
Sos un asistente que clasifica respuestas de contactos inmobiliarios.

Basándote en los siguientes mensajes recibidos del contacto, escribí una clasificación breve (5 a 10 palabras) que describa su postura o reacción.

Mensajes recibidos:
{$conversation}

Respondé ÚNICAMENTE con la clasificación, sin explicaciones ni puntuación al final.
Ejemplos: "no interesado precio comisión", "interesado quiere más información", "colega del sector", "ya vendió sin intermediario", "solicita no ser contactado", "respuesta automática empresa"
PROMPT;

        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $model    = $agent->model ?: 'llama-3.1-8b-instant';

        // Para clasificación puntual usamos el modelo más capaz de Groq
        if (str_contains($model, '8b') || str_contains($model, 'instant')) {
            $model = 'llama-3.3-70b-versatile';
        }

        $response = Http::withToken($agent->api_key)
            ->timeout(20)
            ->post($endpoint, [
                'model'      => $model,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 40,
                'temperature' => 0,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->json('error.message') ?? $response->status());
        }

        $text = trim($response->json('choices.0.message.content') ?? '');

        return $text !== '' ? mb_substr($text, 0, 200) : null;
    }
}
