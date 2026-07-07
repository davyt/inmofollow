<x-filament-panels::page>

<style>
.ai-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:900px){ .ai-grid { grid-template-columns: 1fr; } }
.ai-card    { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 24px; }
.ai-label   { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 6px; }
.ai-input   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.ai-input:focus { border-color: #f59e0b; }
.ai-select  { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.ai-select:focus { border-color: #f59e0b; }
.ai-textarea { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 10px 12px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; resize: vertical; min-height: 180px; line-height: 1.6; font-family: monospace; }
.ai-textarea:focus { border-color: #f59e0b; }
.ai-toggle-wrap { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; }
.ai-toggle  { position: relative; width: 44px; height: 24px; cursor: pointer; }
.ai-toggle input { opacity: 0; width: 0; height: 0; }
.ai-slider  { position: absolute; inset: 0; background: #374151; border-radius: 24px; transition: background .2s; }
.ai-toggle input:checked + .ai-slider { background: #f59e0b; }
.ai-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: transform .2s; }
.ai-toggle input:checked + .ai-slider::before { transform: translateX(20px); }
.ai-btn     { padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
.ai-btn-primary { background: #f59e0b; color: #0f0f1a; }
.ai-btn-primary:hover { opacity: .85; }
.ai-provider-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ai-provider-opt  { border: 1.5px solid #2d2d42; border-radius: 8px; padding: 10px 12px; cursor: pointer; transition: all .15s; }
.ai-provider-opt:hover { border-color: #4b5563; }
.ai-provider-opt.selected { border-color: #f59e0b; background: #1f1a0a; }
.ai-free-badge { display: inline-block; font-size: 9px; font-weight: 700; background: #052e16; color: #4ade80; border-radius: 3px; padding: 1px 5px; margin-left: 4px; vertical-align: middle; }
.ai-info    { background: #0f172a; border: 1px solid #1e3a5f; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #60a5fa; line-height: 1.6; }
</style>

<div class="ai-grid">

    {{-- Columna izquierda: configuración --}}
    <div class="ai-card">

        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">Configuración del agente</div>
            @if($agentId)
            <span style="font-size: 11px; font-weight: 700; border-radius: 999px; padding: 3px 10px; background: {{ $active ? '#052e16' : '#1f2937' }}; color: {{ $active ? '#4ade80' : '#6b7280' }};">
                {{ $active ? '● Activo' : '○ Inactivo' }}
            </span>
            @endif
        </div>

        @if($saveMessage)
        <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 10px 14px; color: #4ade80; font-size: 13px; margin-bottom: 16px;">
            {{ $saveMessage }}
        </div>
        @endif

        {{-- Nombre --}}
        <div style="margin-bottom: 16px;">
            <label class="ai-label">Nombre del agente</label>
            <input type="text" class="ai-input" wire:model="name" placeholder="Ej: Valeria - Asistente inmobiliaria">
        </div>

        {{-- Selector de proveedor --}}
        <div style="margin-bottom: 16px;">
            <label class="ai-label">Proveedor</label>
            <div class="ai-provider-grid">
                @foreach(\App\Filament\Pages\AiAgents::$providers as $key => $info)
                <div
                    class="ai-provider-opt {{ $provider === $key ? 'selected' : '' }}"
                    wire:click="$set('provider', '{{ $key }}')"
                >
                    <div style="font-size: 12px; font-weight: 600; color: {{ $provider === $key ? '#f59e0b' : '#94a3b8' }};">
                        {{ $info['label'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Modelo --}}
        <div style="margin-bottom: 16px;">
            <label class="ai-label">Modelo</label>
            <select class="ai-select" wire:model="model">
                @foreach((\App\Filament\Pages\AiAgents::$models[$provider] ?? []) as $modelKey => $modelLabel)
                <option value="{{ $modelKey }}">{{ $modelLabel }}</option>
                @endforeach
            </select>
        </div>

        {{-- API Key --}}
        <div style="margin-bottom: 16px;">
            <label class="ai-label">
                API Key
                @php $providerInfo = \App\Filament\Pages\AiAgents::$providers[$provider] ?? null; @endphp
                @if($providerInfo)
                <a href="{{ $providerInfo['url'] }}" target="_blank" style="color: #f59e0b; font-weight: 400; text-transform: none; font-size: 11px; margin-left: 6px;">Obtener gratis →</a>
                @endif
            </label>
            <input
                type="password"
                class="ai-input"
                wire:model="apiKey"
                placeholder="{{ $agentId ? '(guardada — escribí para cambiar)' : 'Pegá tu API Key aquí' }}"
                autocomplete="off"
            >
            @if($provider === 'anthropic')
            <div style="font-size: 11px; color: #4b5563; margin-top: 4px;">O dejá vacío para usar la clave configurada en Mi empresa.</div>
            @endif
        </div>

        {{-- Toggles --}}
        <div style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 10px;">
            <div class="ai-toggle-wrap">
                <div>
                    <div style="font-size: 13px; font-weight: 600; color: #e2e8f0;">Activar agente</div>
                    <div style="font-size: 11px; color: #4b5563; margin-top: 2px;">Responde a mensajes WhatsApp entrantes</div>
                </div>
                <label class="ai-toggle">
                    <input type="checkbox" wire:model.live="active">
                    <span class="ai-slider"></span>
                </label>
            </div>
            <div class="ai-toggle-wrap" style="{{ !$active ? 'opacity:.4; pointer-events:none;' : '' }}">
                <div>
                    <div style="font-size: 13px; font-weight: 600; color: #e2e8f0;">Envío automático</div>
                    <div style="font-size: 11px; color: #4b5563; margin-top: 2px;">
                        {{ $autoSend ? 'Envía directo al lead por WhatsApp' : 'Guarda como borrador en el Inbox' }}
                    </div>
                </div>
                <label class="ai-toggle">
                    <input type="checkbox" wire:model.live="autoSend">
                    <span class="ai-slider"></span>
                </label>
            </div>
        </div>

        {{-- Prompt --}}
        <div style="margin-bottom: 20px;">
            <label class="ai-label">Prompt del sistema</label>
            <textarea class="ai-textarea" wire:model="systemPrompt" placeholder="Describí la personalidad y reglas del agente..."></textarea>
        </div>

        <button class="ai-btn ai-btn-primary" wire:click="save">Guardar configuración</button>
    </div>

    {{-- Columna derecha: info --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 14px;">🆓 Opciones gratuitas recomendadas</div>
            <div style="display: flex; flex-direction: column; gap: 12px;">

                <div style="border: 1px solid #2d2d42; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 3px;">
                        Groq <span class="ai-free-badge">GRATIS</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                        El más rápido. Llama 3.3 70B gratis con límite generoso (14.400 req/día). Ideal para producción.
                    </div>
                </div>

                <div style="border: 1px solid #2d2d42; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 3px;">
                        OpenRouter <span class="ai-free-badge">GRATIS</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                        Acceso a muchos modelos gratuitos (Llama, Mistral, Phi). Requiere registración. Límites más bajos.
                    </div>
                </div>

                <div style="border: 1px solid #2d2d42; border-radius: 8px; padding: 12px 14px;">
                    <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 3px;">
                        Google Gemini <span class="ai-free-badge">GRATIS</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                        Gemini 1.5 Flash gratis hasta 1.500 req/día. Muy buena calidad para el precio.
                    </div>
                </div>

            </div>
        </div>

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">🤖 Cómo funciona</div>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #64748b; line-height: 1.6;">
                <div style="display: flex; gap: 10px;"><span style="color:#f59e0b;flex-shrink:0;">1.</span><span>Llega un WhatsApp al lead</span></div>
                <div style="display: flex; gap: 10px;"><span style="color:#f59e0b;flex-shrink:0;">2.</span><span>El agente lee el historial de la conversación y el contexto del lead</span></div>
                <div style="display: flex; gap: 10px;"><span style="color:#f59e0b;flex-shrink:0;">3.</span>
                    <span>
                        @if($autoSend)
                        <strong style="color:#e2e8f0;">Auto-envío:</strong> responde directo por WhatsApp
                        @else
                        <strong style="color:#e2e8f0;">Borrador:</strong> aparece en el Inbox con botón Enviar/Descartar
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px;">📋 Contexto disponible</div>
            <div class="ai-info">
                El agente recibe automáticamente:<br><br>
                • Nombre, zona y tipo de propiedad del lead<br>
                • Últimos 10 mensajes de la conversación<br><br>
                El agente solo responde dentro de la ventana de 24hs de WhatsApp.
            </div>
        </div>

    </div>
</div>

</x-filament-panels::page>
