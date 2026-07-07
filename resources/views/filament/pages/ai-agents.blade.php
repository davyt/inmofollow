<x-filament-panels::page>

<style>
.ai-grid    { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:900px){ .ai-grid { grid-template-columns: 1fr; } }
.ai-card    { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 24px; }
.ai-label   { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 6px; }
.ai-input   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.ai-input:focus { border-color: #f59e0b; }
.ai-textarea { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 10px 12px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; resize: vertical; min-height: 220px; line-height: 1.6; font-family: monospace; }
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
.ai-info    { background: #0f172a; border: 1px solid #1e3a5f; border-radius: 8px; padding: 14px 16px; font-size: 13px; color: #60a5fa; line-height: 1.6; }
.ai-badge   { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 3px 10px; }
</style>

<div class="ai-grid">

    {{-- Columna izquierda: configuración --}}
    <div class="ai-card">

        {{-- Header con estado --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">Configuración del agente</div>
            @if($agentId)
            <span class="ai-badge {{ $active ? 'bg-green-900 text-green-400' : 'bg-gray-800 text-gray-500' }}"
                  style="background: {{ $active ? '#052e16' : '#1f2937' }}; color: {{ $active ? '#4ade80' : '#6b7280' }};">
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

        {{-- Toggles --}}
        <div style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 10px;">
            <div class="ai-toggle-wrap">
                <div>
                    <div style="font-size: 13px; font-weight: 600; color: #e2e8f0;">Activar agente</div>
                    <div style="font-size: 11px; color: #4b5563; margin-top: 2px;">Responde automáticamente a mensajes entrantes</div>
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
                        @if($autoSend)
                        Envía la respuesta directo al lead por WhatsApp
                        @else
                        Guarda la respuesta como borrador para que la revisés
                        @endif
                    </div>
                </div>
                <label class="ai-toggle">
                    <input type="checkbox" wire:model.live="autoSend">
                    <span class="ai-slider"></span>
                </label>
            </div>
        </div>

        {{-- System prompt --}}
        <div style="margin-bottom: 20px;">
            <label class="ai-label">Prompt del sistema (personalidad y reglas)</label>
            <textarea class="ai-textarea" wire:model="systemPrompt" placeholder="Describí cómo debe comportarse el agente..."></textarea>
        </div>

        <button class="ai-btn ai-btn-primary" wire:click="save">
            Guardar configuración
        </button>
    </div>

    {{-- Columna derecha: info y variables --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 14px;">🤖 Cómo funciona</div>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px; color: #64748b; line-height: 1.6;">
                <div style="display: flex; gap: 10px;">
                    <span style="color: #f59e0b; flex-shrink:0;">1.</span>
                    <span>Llega un mensaje de WhatsApp de un lead</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <span style="color: #f59e0b; flex-shrink:0;">2.</span>
                    <span>El agente IA lee el contexto del lead y el historial de la conversación</span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <span style="color: #f59e0b; flex-shrink:0;">3.</span>
                    <span>
                        @if($autoSend)
                        <strong style="color:#e2e8f0;">Envío automático:</strong> manda la respuesta directamente al lead por WhatsApp
                        @else
                        <strong style="color:#e2e8f0;">Modo borrador:</strong> guarda la respuesta sugerida en el Inbox para que la revisés antes de enviar
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">📋 Contexto disponible para el agente</div>
            <div class="ai-info">
                El agente recibe automáticamente:<br><br>
                • <strong>Nombre</strong> del lead<br>
                • <strong>Zona</strong> de interés<br>
                • <strong>Tipo de propiedad</strong> buscada<br>
                • <strong>Historial</strong> de los últimos 10 mensajes de la conversación<br><br>
                Podés referenciar este contexto en tu prompt para personalizar las respuestas.
            </div>
        </div>

        <div class="ai-card">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 12px;">⚠️ Requisitos</div>
            <div style="font-size: 13px; color: #64748b; line-height: 1.7;">
                • <strong style="color:#94a3b8;">API Key de Anthropic</strong> configurada en <a href="/davyt/companies" style="color:#f59e0b;">Configuración → Mi empresa</a><br>
                • El agente solo responde dentro de la ventana de 24hs de WhatsApp<br>
                • En modo borrador, la sugerencia aparece en el <a href="/davyt/inbox" style="color:#f59e0b;">Inbox</a> con botones para enviar o descartar
            </div>
        </div>

    </div>
</div>

</x-filament-panels::page>
