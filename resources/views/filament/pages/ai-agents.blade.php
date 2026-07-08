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
                • Últimos 10 mensajes de la conversación<br>
                • Base de conocimiento activa (abajo)<br><br>
                El agente solo responde dentro de la ventana de 24hs de WhatsApp.
            </div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #2d2d42;display:flex;gap:8px;flex-wrap:wrap;">
                <a href="/davyt/flows"
                   style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#94a3b8;text-decoration:none;padding:5px 12px;background:#13131f;border:1px solid #2d2d42;border-radius:6px;"
                   onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#2d2d42'">
                    🔄 Configurar Flows
                </a>
                <a href="/davyt/broadcasts"
                   style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#94a3b8;text-decoration:none;padding:5px 12px;background:#13131f;border:1px solid #2d2d42;border-radius:6px;"
                   onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#2d2d42'">
                    📢 Ir a Broadcasts
                </a>
            </div>
        </div>

    </div>
</div>

{{-- ============================================================ --}}
{{-- Base de conocimiento --}}
{{-- ============================================================ --}}
<div style="margin-top: 20px;" class="ai-card">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
        <div>
            <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">📚 Base de conocimiento</div>
            <div style="font-size: 12px; color: #4b5563; margin-top: 2px;">El agente usará este contexto al responder. Máx. 6.000 caracteres por consulta.</div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="ai-btn" style="background:#23233a;color:#94a3b8;border:1px solid #2d2d42;font-size:11px;padding:6px 12px;" wire:click="openAddForm('text')">+ Texto</button>
            <button class="ai-btn" style="background:#23233a;color:#94a3b8;border:1px solid #2d2d42;font-size:11px;padding:6px 12px;" wire:click="openAddForm('url')">+ URL</button>
            <button class="ai-btn" style="background:#23233a;color:#94a3b8;border:1px solid #2d2d42;font-size:11px;padding:6px 12px;" wire:click="openAddForm('pdf')">+ PDF</button>
        </div>
    </div>

    {{-- Formulario de agregar entrada --}}
    @if($showAddForm)
    <div style="background:#13131f;border:1px solid #2d2d42;border-radius:10px;padding:18px;margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:#e2e8f0;margin-bottom:14px;">
            {{ $kbType === 'text' ? '📝 Texto libre' : ($kbType === 'url' ? '🔗 Desde URL' : '📄 Desde PDF') }}
        </div>

        @if($kbMessage)
        <div style="background:#450a0a;border:1px solid #7f1d1d;border-radius:6px;padding:8px 12px;color:#f87171;font-size:12px;margin-bottom:12px;">{{ $kbMessage }}</div>
        @endif

        <div style="margin-bottom:12px;">
            <label class="ai-label">Título (opcional)</label>
            <input type="text" class="ai-input" wire:model="kbTitle" placeholder="Ej: Información de la empresa, Listado de propiedades...">
        </div>

        @if($kbType === 'text')
        <div style="margin-bottom:14px;">
            <label class="ai-label">Contenido</label>
            <textarea class="ai-textarea" wire:model="kbText" placeholder="Pegá aquí el texto que el agente debe conocer..." style="min-height:140px;"></textarea>
        </div>

        @elseif($kbType === 'url')
        <div style="margin-bottom:14px;">
            <label class="ai-label">URL</label>
            <input type="url" class="ai-input" wire:model="kbUrl" placeholder="https://...">
            <div style="font-size:11px;color:#4b5563;margin-top:4px;">Se descargará el contenido de texto de la página (funciona mejor con páginas estáticas).</div>
        </div>

        @elseif($kbType === 'pdf')
        <div style="margin-bottom:14px;">
            <label class="ai-label">Archivo PDF</label>
            <input type="file" wire:model="kbFile" accept=".pdf"
                   style="width:100%;color:#94a3b8;font-size:13px;padding:8px 0;">
            <div style="font-size:11px;color:#4b5563;margin-top:4px;">Máx. 10MB. Se extraerá el texto del PDF automáticamente.</div>
        </div>
        @endif

        <div style="display:flex;gap:8px;">
            <button class="ai-btn ai-btn-primary" wire:click="addEntry" wire:loading.attr="disabled" wire:target="addEntry,kbFile">
                <span wire:loading.remove wire:target="addEntry">Guardar</span>
                <span wire:loading wire:target="addEntry">Procesando...</span>
            </button>
            <button class="ai-btn" style="background:#23233a;color:#94a3b8;border:1px solid #2d2d42;" wire:click="cancelAdd">Cancelar</button>
        </div>
    </div>
    @endif

    {{-- Lista de entradas --}}
    @if(empty($entries))
    <div style="padding:32px;text-align:center;color:#374151;font-size:13px;">
        No hay entradas todavía. Agregá textos, URLs o PDFs para enriquecer las respuestas del agente.
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($entries as $entry)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#13131f;border:1px solid #2d2d42;border-radius:8px;">
            <span style="font-size:18px;flex-shrink:0;">
                {{ $entry['type'] === 'pdf' ? '📄' : ($entry['type'] === 'url' ? '🔗' : '📝') }}
            </span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:{{ $entry['active'] ? '#e2e8f0' : '#4b5563' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $entry['title'] }}
                </div>
                @if(!empty($entry['source_url']))
                <div style="font-size:11px;color:#4b5563;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    {{ $entry['source_url'] }}
                </div>
                @endif
            </div>
            <span style="font-size:10px;font-weight:700;border-radius:999px;padding:2px 8px;flex-shrink:0;background:{{ $entry['active'] ? '#052e16' : '#1f2937' }};color:{{ $entry['active'] ? '#4ade80' : '#6b7280' }};">
                {{ $entry['active'] ? 'Activo' : 'Inactivo' }}
            </span>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button
                    wire:click="toggleEntry({{ $entry['id'] }})"
                    style="font-size:11px;padding:4px 10px;border-radius:5px;border:1px solid #2d2d42;background:#23233a;color:#94a3b8;cursor:pointer;"
                >{{ $entry['active'] ? 'Pausar' : 'Activar' }}</button>
                <button
                    wire:click="deleteEntry({{ $entry['id'] }})"
                    onclick="return confirm('¿Eliminar esta entrada?')"
                    style="font-size:11px;padding:4px 10px;border-radius:5px;border:1px solid #7f1d1d;background:#450a0a;color:#f87171;cursor:pointer;"
                >Eliminar</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

{{-- ============================================================ --}}
{{-- Playground --}}
{{-- ============================================================ --}}
<div style="margin-top: 20px;" class="ai-card">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">
                🧪 Playground
                <span style="font-size: 10px; font-weight: 700; background: #1e3a5f; color: #60a5fa; border-radius: 3px; padding: 2px 7px; margin-left: 8px; vertical-align: middle; letter-spacing: .04em;">SIN EFECTOS REALES</span>
            </div>
            <div style="font-size: 12px; color: #4b5563; margin-top: 3px;">Probá el agente como si fueras el lead. Las acciones <code style="font-size:10px;background:#13131f;padding:1px 4px;border-radius:3px;">[ESTADO]</code> <code style="font-size:10px;background:#13131f;padding:1px 4px;border-radius:3px;">[AGENTE]</code> se detectan pero no se ejecutan.</div>
        </div>
        @if(!empty($pgHistory))
        <button class="ai-btn" style="background:#23233a;color:#94a3b8;border:1px solid #2d2d42;font-size:11px;padding:6px 14px;" wire:click="clearPlayground">Limpiar chat</button>
        @endif
    </div>

    {{-- Lead de contexto --}}
    @if(!empty($pgLeads))
    <div style="margin-bottom: 16px;">
        <label class="ai-label">Lead de contexto (opcional)</label>
        <select class="ai-select" wire:model.live="pgLeadId" style="max-width: 380px;">
            <option value="">Sin lead — modo prueba genérico</option>
            @foreach($pgLeads as $l)
            <option value="{{ $l['id'] }}">{{ $l['name'] }}{{ $l['phone'] ? ' (' . $l['phone'] . ')' : '' }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Ventana de chat --}}
    <div id="pg-chat" style="background:#0d0d1a;border:1px solid #2d2d42;border-radius:10px;padding:16px;min-height:200px;max-height:440px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;margin-bottom:12px;">

        @if(empty($pgHistory))
        <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#374151;font-size:13px;padding:40px 0;">
            Escribí un mensaje para comenzar a probar el agente...
        </div>
        @else

        @foreach($pgHistory as $msg)

            @if($msg['role'] === 'user')
            {{-- Mensaje del usuario (derecha, simula el lead) --}}
            <div style="display:flex;justify-content:flex-end;">
                <div style="background:#1e3a5f;color:#bfdbfe;border-radius:14px 14px 2px 14px;padding:10px 14px;max-width:78%;font-size:13px;line-height:1.55;word-break:break-word;">
                    {{ $msg['content'] }}
                </div>
            </div>

            @else
            {{-- Respuesta del agente (izquierda) --}}
            <div style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;">
                <div style="display:flex;align-items:flex-end;gap:8px;">
                    <span style="font-size:18px;flex-shrink:0;line-height:1;">🤖</span>
                    <div style="background:#1a1a2e;border:1px solid #2d2d42;color:#e2e8f0;border-radius:14px 14px 14px 2px;padding:10px 14px;max-width:80%;font-size:13px;line-height:1.55;word-break:break-word;">
                        {{ $msg['content'] }}
                    </div>
                </div>
                @if(!empty($msg['actions']))
                <div style="display:flex;flex-wrap:wrap;gap:6px;padding-left:30px;">
                    @foreach($msg['actions'] as $action)
                    <span style="font-size:10px;font-weight:700;background:#052e16;color:#4ade80;border-radius:4px;padding:3px 9px;border:1px solid #16653444;">
                        ⚡ {{ $action['label'] }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

        @endforeach
        @endif

    </div>

    @if($pgError)
    <div style="background:#450a0a;border:1px solid #7f1d1d;border-radius:6px;padding:8px 12px;color:#f87171;font-size:12px;margin-bottom:10px;">{{ $pgError }}</div>
    @endif

    @if(! $agentId)
    <div class="ai-info" style="margin-bottom: 0;">Configurá y guardá el agente arriba para poder usar el playground.</div>
    @else
    {{-- Input --}}
    <div style="display:flex;gap:10px;">
        <input
            type="text"
            class="ai-input"
            wire:model="pgInput"
            wire:keydown.enter="sendPlayground"
            placeholder="Escribí como si fueras el lead..."
            style="flex:1;"
        >
        <button
            class="ai-btn ai-btn-primary"
            wire:click="sendPlayground"
            wire:loading.attr="disabled"
            wire:target="sendPlayground"
            style="flex-shrink:0;min-width:80px;"
        >
            <span wire:loading.remove wire:target="sendPlayground">Enviar</span>
            <span wire:loading wire:target="sendPlayground">...</span>
        </button>
    </div>
    @endif

</div>

<script>
document.addEventListener('livewire:updated', function () {
    var chat = document.getElementById('pg-chat');
    if (chat) chat.scrollTop = chat.scrollHeight;
});
</script>

</x-filament-panels::page>
