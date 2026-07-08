<x-filament-panels::page>

<style>
.wa-card    { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 28px; max-width: 720px; }
.wa-section { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: 24px 0 12px; border-top: 1px solid #2d2d42; padding-top: 20px; }
.wa-section:first-of-type { margin-top: 0; border-top: none; padding-top: 0; }
.wa-label   { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 6px; }
.wa-input   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.wa-input:focus { border-color: #25d366; }
.wa-toggle-wrap { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; }
.wa-toggle  { position: relative; width: 44px; height: 24px; cursor: pointer; }
.wa-toggle input { opacity: 0; width: 0; height: 0; }
.wa-slider  { position: absolute; inset: 0; background: #374151; border-radius: 24px; transition: background .2s; }
.wa-toggle input:checked + .wa-slider { background: #25d366; }
.wa-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: transform .2s; }
.wa-toggle input:checked + .wa-slider::before { transform: translateX(20px); }
.wa-btn     { padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
.wa-btn-primary { background: #f59e0b; color: #0f0f1a; }
.wa-btn-primary:hover { opacity: .85; }
.wa-btn-ghost { background: #1e293b; color: #94a3b8; border: 1px solid #2d2d42; margin-left: 10px; }
.wa-btn-ghost:hover { border-color: #4b5563; color: #e2e8f0; }
.wa-field   { margin-bottom: 16px; }
.wa-hint    { font-size: 11px; color: #64748b; margin-top: 4px; }
.wa-info    { background: #0f172a; border: 1px solid #1e3a5f; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #60a5fa; line-height: 1.6; }
.wa-code    { background: #0d0d1a; border: 1px solid #2d2d42; border-radius: 6px; padding: 8px 12px; font-family: monospace; font-size: 13px; color: #a5b4fc; word-break: break-all; display: block; margin-top: 6px; }
</style>

<div class="wa-card">

    @if($saveMessage)
    <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 10px 14px; color: #4ade80; font-size: 13px; margin-bottom: 20px;">
        {{ $saveMessage }}
    </div>
    @endif

    @if($connectionStatus === 'ok')
    <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 10px 14px; color: #4ade80; font-size: 13px; margin-bottom: 20px;">
        🟢 Conexión exitosa con la API de WhatsApp.
    </div>
    @elseif(str_starts_with($connectionStatus, 'error:'))
    <div style="background: #1c0a0a; border: 1px solid #ef444444; border-radius: 8px; padding: 10px 14px; color: #f87171; font-size: 13px; margin-bottom: 20px;">
        🔴 {{ substr($connectionStatus, 6) }}
    </div>
    @endif

    {{-- Activar WhatsApp --}}
    <div class="wa-section">Estado</div>

    <div class="wa-field">
        <div class="wa-toggle-wrap">
            <div>
                <span style="font-size: 14px; color: #e2e8f0; font-weight: 600;">Activar envío automático por WhatsApp</span>
                <p class="wa-hint" style="margin-top: 4px;">Activá solo cuando tengas configuradas las credenciales de abajo.</p>
            </div>
            <label class="wa-toggle">
                <input type="checkbox" wire:model="waActive">
                <span class="wa-slider"></span>
            </label>
        </div>
    </div>

    {{-- Credenciales --}}
    <div class="wa-section">Credenciales de Meta</div>

    <div class="wa-info" style="margin-bottom: 20px;">
        Obtené estas credenciales en <strong>Meta for Developers</strong> → Tu App → WhatsApp → Configuración de la API.
        Creá una app de tipo <em>Business</em> si aún no tenés una.
    </div>

    <div class="wa-field">
        <label class="wa-label">Business Account ID (WABA ID)</label>
        <input type="text" class="wa-input" wire:model="waBusinessAccountId" placeholder="123456789012345">
        <p class="wa-hint">Meta Business Manager → Configuración → ID de cuenta de WhatsApp Business.</p>
    </div>

    <div class="wa-field">
        <label class="wa-label">Phone Number ID</label>
        <input type="text" class="wa-input" wire:model="waPhoneNumberId" placeholder="123456789012345">
        <p class="wa-hint">Meta for Developers → Tu App → WhatsApp → Configuración de la API → Phone Number ID.</p>
    </div>

    <div class="wa-field">
        <label class="wa-label">Access Token (permanente)</label>
        <input type="password" class="wa-input" wire:model="waAccessToken" placeholder="Dejá en blanco para mantener el actual">
        <p class="wa-hint">Token de acceso permanente. Se guarda encriptado. Solo ingresá uno nuevo para reemplazar el actual.</p>
    </div>

    {{-- Webhook --}}
    <div class="wa-section">Configuración del Webhook</div>

    <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">
        Copiá estos valores en <strong>Meta for Developers → Tu App → WhatsApp → Configuración → Webhooks</strong>.
    </p>

    <div class="wa-field">
        <label class="wa-label">Webhook URL</label>
        <span class="wa-code">{{ url('/webhooks/whatsapp') }}</span>
    </div>

    <div class="wa-field">
        <label class="wa-label">Verify Token</label>
        <span class="wa-code">{{ config('services.whatsapp.verify_token') }}</span>
    </div>

    <div class="wa-info" style="margin-top: 4px;">
        Suscribite a los eventos: <strong>messages</strong> y <strong>message_echoes</strong>.
    </div>

    {{-- Anthropic --}}
    <div class="wa-section">API Key de Anthropic (opcional)</div>

    <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">
        Solo necesario si usás Anthropic como proveedor de IA o para generación de plantillas.
        Obtenela en <strong>console.anthropic.com → API Keys</strong>.
    </p>

    <div class="wa-field">
        <label class="wa-label">API Key de Anthropic</label>
        <input type="password" class="wa-input" wire:model="anthropicApiKey" placeholder="sk-ant-… (dejá en blanco para mantener)">
        <p class="wa-hint">Se guarda encriptada. Solo ingresá una nueva para reemplazar la actual.</p>
    </div>

    {{-- Acciones --}}
    <div style="margin-top: 28px; display: flex; align-items: center;">
        <button class="wa-btn wa-btn-primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Guardar configuración</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </button>
        <button class="wa-btn wa-btn-ghost" wire:click="testConnection" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="testConnection">Probar conexión</span>
            <span wire:loading wire:target="testConnection">Probando…</span>
        </button>
    </div>

</div>

</x-filament-panels::page>
