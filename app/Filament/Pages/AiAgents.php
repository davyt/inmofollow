<?php

namespace App\Filament\Pages;

use App\Models\AiAgent;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class AiAgents extends Page
{
    protected static ?string                 $navigationLabel = 'Agentes IA';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-cpu-chip';
    protected static ?string                 $title           = 'Agentes IA';
    protected static ?int                    $navigationSort  = 5;
    protected string                         $view            = 'filament.pages.ai-agents';

    public ?int    $agentId      = null;
    public string  $name         = 'Agente IA';
    public string  $systemPrompt = '';
    public bool    $active       = false;
    public bool    $autoSend     = false;
    public string  $saveMessage  = '';

    public function mount(): void
    {
        $user  = Auth::user();
        $agent = AiAgent::where('company_id', $user->company_id)->first();

        if ($agent) {
            $this->agentId      = $agent->id;
            $this->name         = $agent->name;
            $this->systemPrompt = $agent->system_prompt;
            $this->active       = $agent->active;
            $this->autoSend     = $agent->auto_send;
        } else {
            $this->systemPrompt = $this->defaultPrompt();
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = [
            'company_id'    => $user->company_id,
            'name'          => $this->name ?: 'Agente IA',
            'system_prompt' => $this->systemPrompt,
            'active'        => $this->active,
            'auto_send'     => $this->autoSend,
        ];

        if ($this->agentId) {
            AiAgent::find($this->agentId)?->update($data);
        } else {
            $agent          = AiAgent::create($data);
            $this->agentId  = $agent->id;
        }

        $this->saveMessage = '✓ Configuración guardada.';
    }

    private function defaultPrompt(): string
    {
        return "Sos un asistente inmobiliario amigable que responde consultas de clientes interesados en propiedades en Uruguay.\n\nReglas:\n- Respondé siempre en español, con tuteo (vos/te)\n- Sé conciso y cálido, máximo 3-4 líneas por respuesta\n- Si el cliente pregunta por precios o disponibilidad, decile que un agente lo va a contactar pronto\n- No inventes información sobre propiedades específicas\n- Si no sabés algo, ofrecé comunicar con el agente responsable";
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::ExtraLarge;
    }
}
