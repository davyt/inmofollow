<?php

namespace App\Filament\Pages;

use App\Models\AiAgent;
use App\Models\AiKnowledgeEntry;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class AiAgents extends Page
{
    use WithFileUploads;

    protected static ?string                 $navigationLabel = 'Agentes IA';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-cpu-chip';
    protected static ?string                 $title           = 'Agentes IA';
    protected static ?int                    $navigationSort  = 1;
    protected static \UnitEnum|string|null         $navigationGroup = 'IA';
    protected string                         $view            = 'filament.pages.ai-agents';

    // Agent config
    public ?int    $agentId      = null;
    public string  $name         = 'Agente IA';
    public string  $provider     = 'groq';
    public string  $model        = '';
    public string  $apiKey       = '';
    public string  $systemPrompt = '';
    public bool    $active       = false;
    public bool    $autoSend     = false;
    public string  $saveMessage  = '';

    // Knowledge base
    public array   $entries      = [];
    public bool    $showAddForm  = false;
    public string  $kbType       = 'text';
    public string  $kbTitle      = '';
    public string  $kbText       = '';
    public string  $kbUrl        = '';
    public         $kbFile       = null;
    public string  $kbMessage    = '';
    public bool    $kbLoading    = false;

    public static array $providers = [
        'groq'       => ['label' => 'Groq (gratuito)',       'url' => 'https://console.groq.com/keys'],
        'openrouter' => ['label' => 'OpenRouter (gratuito)', 'url' => 'https://openrouter.ai/keys'],
        'gemini'     => ['label' => 'Google Gemini',         'url' => 'https://aistudio.google.com/apikey'],
        'openai'     => ['label' => 'OpenAI (GPT)',          'url' => 'https://platform.openai.com/api-keys'],
        'anthropic'  => ['label' => 'Anthropic (Claude)',    'url' => 'https://console.anthropic.com/keys'],
    ];

    public static array $models = [
        'groq'       => [
            'llama-3.1-8b-instant'    => 'Llama 3.1 8B Instant (rápido, gratuito)',
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (mejor calidad, gratuito)',
            'mixtral-8x7b-32768'      => 'Mixtral 8x7B (gratuito)',
            'gemma2-9b-it'            => 'Gemma 2 9B (gratuito)',
        ],
        'openrouter' => [
            'meta-llama/llama-3.1-8b-instruct:free'    => 'Llama 3.1 8B (gratuito)',
            'google/gemma-2-9b-it:free'                => 'Gemma 2 9B (gratuito)',
            'mistralai/mistral-7b-instruct:free'       => 'Mistral 7B (gratuito)',
            'microsoft/phi-3-mini-128k-instruct:free'  => 'Phi-3 Mini (gratuito)',
        ],
        'gemini'     => [
            'gemini-1.5-flash'    => 'Gemini 1.5 Flash (tier gratuito)',
            'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B (tier gratuito)',
            'gemini-1.5-pro'      => 'Gemini 1.5 Pro (pago)',
        ],
        'openai'     => [
            'gpt-4o-mini'   => 'GPT-4o Mini (más barato)',
            'gpt-4o'        => 'GPT-4o',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        ],
        'anthropic'  => [
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (rápido)',
            'claude-sonnet-5'           => 'Claude Sonnet 5',
        ],
    ];

    public function mount(): void
    {
        $user  = Auth::user();
        $agent = AiAgent::where('company_id', $user->company_id)->first();

        if ($agent) {
            $this->agentId      = $agent->id;
            $this->name         = $agent->name;
            $this->provider     = $agent->provider ?? 'groq';
            $this->model        = $agent->model ?? '';
            $this->apiKey       = '';
            $this->systemPrompt = $agent->system_prompt;
            $this->active       = $agent->active;
            $this->autoSend     = $agent->auto_send;
        } else {
            $this->systemPrompt = $this->defaultPrompt();
            $this->model        = 'llama-3.1-8b-instant';
        }

        $this->loadEntries();
    }

    public function loadEntries(): void
    {
        $user          = Auth::user();
        $this->entries = AiKnowledgeEntry::where('company_id', $user->company_id)
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'type', 'source_url', 'active', 'created_at'])
            ->toArray();
    }

    // --- Agent config ---

    public function updatedProvider(string $value): void
    {
        $models      = self::$models[$value] ?? [];
        $this->model = array_key_first($models) ?? '';
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = [
            'company_id'    => $user->company_id,
            'name'          => $this->name ?: 'Agente IA',
            'provider'      => $this->provider,
            'model'         => $this->model,
            'system_prompt' => $this->systemPrompt,
            'active'        => $this->active,
            'auto_send'     => $this->autoSend,
        ];

        if ($this->apiKey !== '') {
            $data['api_key'] = $this->apiKey;
        }

        if ($this->agentId) {
            AiAgent::find($this->agentId)?->update($data);
        } else {
            $agent         = AiAgent::create($data);
            $this->agentId = $agent->id;
        }

        $this->apiKey      = '';
        $this->saveMessage = '✓ Configuración guardada.';
    }

    // --- Knowledge base ---

    public function openAddForm(string $type = 'text'): void
    {
        $this->kbType    = $type;
        $this->kbTitle   = '';
        $this->kbText    = '';
        $this->kbUrl     = '';
        $this->kbFile    = null;
        $this->kbMessage = '';
        $this->showAddForm = true;
    }

    public function cancelAdd(): void
    {
        $this->showAddForm = false;
        $this->kbFile      = null;
    }

    public function addEntry(): void
    {
        $user    = Auth::user();
        $content = '';
        $source  = null;
        $path    = null;

        try {
            if ($this->kbType === 'text') {
                if (empty(trim($this->kbText))) {
                    $this->kbMessage = 'Ingresá el texto.';
                    return;
                }
                $content = trim($this->kbText);

            } elseif ($this->kbType === 'url') {
                if (empty(trim($this->kbUrl))) {
                    $this->kbMessage = 'Ingresá la URL.';
                    return;
                }
                $source  = trim($this->kbUrl);
                $content = $this->fetchUrl($source);

            } elseif ($this->kbType === 'pdf') {
                if (! $this->kbFile) {
                    $this->kbMessage = 'Seleccioná un PDF.';
                    return;
                }
                $path    = $this->kbFile->store('ai-knowledge', 'local');
                $content = $this->extractPdf(Storage::disk('local')->path($path));
            }

            if (empty(trim($content))) {
                $this->kbMessage = 'No se pudo extraer contenido.';
                return;
            }

            AiKnowledgeEntry::create([
                'company_id'   => $user->company_id,
                'ai_agent_id'  => $this->agentId,
                'title'        => $this->kbTitle ?: ($this->kbType === 'url' ? $this->kbUrl : 'Entrada ' . now()->format('d/m H:i')),
                'type'         => $this->kbType,
                'source_url'   => $source,
                'file_path'    => $path,
                'content'      => mb_substr($content, 0, 20000),
                'active'       => true,
            ]);

            $this->showAddForm = false;
            $this->kbFile      = null;
            $this->kbMessage   = '';
            $this->loadEntries();

        } catch (\Throwable $e) {
            $this->kbMessage = 'Error: ' . $e->getMessage();
            Log::error('KB entry error: ' . $e->getMessage());
        }
    }

    public function toggleEntry(int $id): void
    {
        $entry = AiKnowledgeEntry::find($id);
        if ($entry) {
            $entry->update(['active' => ! $entry->active]);
            $this->loadEntries();
        }
    }

    public function deleteEntry(int $id): void
    {
        $entry = AiKnowledgeEntry::find($id);
        if ($entry) {
            if ($entry->file_path) {
                Storage::disk('local')->delete($entry->file_path);
            }
            $entry->delete();
            $this->loadEntries();
        }
    }

    private function fetchUrl(string $url): string
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; InmoFollow/1.0)'])
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("No se pudo acceder a la URL (HTTP {$response->status()}).");
        }

        $html = $response->body();

        // Remove scripts, styles, nav, footer
        $html = preg_replace('/<(script|style|nav|footer|header)[^>]*>.*?<\/\1>/is', '', $html);
        $html = strip_tags($html);
        $html = preg_replace('/\s{3,}/', "\n\n", $html);

        return trim($html);
    }

    private function extractPdf(string $filePath): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException('Librería PDF no disponible.');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    private function defaultPrompt(): string
    {
        return "Sos un asistente inmobiliario amigable que responde consultas de clientes interesados en propiedades en Uruguay.\n\nReglas:\n- Respondé siempre en español, con tuteo (vos/te)\n- Sé conciso y cálido, máximo 3-4 líneas por respuesta\n- Si el cliente pregunta por precios o disponibilidad, decile que un agente lo va a contactar pronto\n- No inventes información sobre propiedades específicas\n- Si no sabés algo, ofrecé comunicar con el agente responsable";
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
