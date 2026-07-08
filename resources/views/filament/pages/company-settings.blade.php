<x-filament-panels::page>

<style>
.cs-card    { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 28px; max-width: 720px; }
.cs-section { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: 24px 0 12px; border-top: 1px solid #2d2d42; padding-top: 20px; }
.cs-section:first-of-type { margin-top: 0; border-top: none; padding-top: 0; }
.cs-label   { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 6px; }
.cs-input   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.cs-input:focus { border-color: #f59e0b; }
.cs-textarea { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 10px 12px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; resize: vertical; min-height: 90px; line-height: 1.6; font-family: monospace; }
.cs-textarea:focus { border-color: #f59e0b; }
.cs-toggle-wrap { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; }
.cs-toggle  { position: relative; width: 44px; height: 24px; cursor: pointer; }
.cs-toggle input { opacity: 0; width: 0; height: 0; }
.cs-slider  { position: absolute; inset: 0; background: #374151; border-radius: 24px; transition: background .2s; }
.cs-toggle input:checked + .cs-slider { background: #f59e0b; }
.cs-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: transform .2s; }
.cs-toggle input:checked + .cs-slider::before { transform: translateX(20px); }
.cs-btn     { padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; background: #f59e0b; color: #0f0f1a; }
.cs-btn:hover { opacity: .85; }
.cs-field   { margin-bottom: 16px; }
.cs-hint    { font-size: 11px; color: #64748b; margin-top: 4px; }
</style>

<div class="cs-card">

    @if($saveMessage)
    <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 10px 14px; color: #4ade80; font-size: 13px; margin-bottom: 20px;">
        {{ $saveMessage }}
    </div>
    @endif

    {{-- Datos básicos --}}
    <div class="cs-section">Datos de la empresa</div>

    <div class="cs-field">
        <label class="cs-label">Nombre</label>
        <input type="text" class="cs-input" wire:model="name" placeholder="Nombre de tu empresa">
    </div>

    <div class="cs-field">
        <label class="cs-label">Email de contacto</label>
        <input type="email" class="cs-input" wire:model="email" placeholder="contacto@empresa.com">
    </div>

    <div class="cs-field">
        <label class="cs-label">Teléfono</label>
        <input type="tel" class="cs-input" wire:model="phone" placeholder="+598 99 000 000">
    </div>

    <div class="cs-field">
        <label class="cs-label">Logo (URL)</label>
        <input type="url" class="cs-input" wire:model="logo" placeholder="https://…/logo.png">
    </div>

    <div class="cs-field">
        <div class="cs-toggle-wrap">
            <span style="font-size: 14px; color: #e2e8f0;">Empresa activa</span>
            <label class="cs-toggle">
                <input type="checkbox" wire:model="active">
                <span class="cs-slider"></span>
            </label>
        </div>
    </div>

    {{-- Opciones de campos de leads --}}
    <div class="cs-section">Opciones de campos (leads)</div>
    <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">Una opción por línea. Se usan en los selectores de leads.</p>

    <div class="cs-field">
        <label class="cs-label">Zonas</label>
        <textarea class="cs-textarea" wire:model="zoneOptions" placeholder="Montevideo&#10;Canelones&#10;Maldonado"></textarea>
        <p class="cs-hint">Ej: Montevideo, Canelones, Punta del Este…</p>
    </div>

    <div class="cs-field">
        <label class="cs-label">Tipos de propiedad</label>
        <textarea class="cs-textarea" wire:model="propertyTypeOptions" placeholder="Casa&#10;Apartamento&#10;Local comercial"></textarea>
    </div>

    <div class="cs-field">
        <label class="cs-label">Fuentes de lead</label>
        <textarea class="cs-textarea" wire:model="leadSourceOptions" placeholder="WhatsApp&#10;Web&#10;Referido"></textarea>
    </div>

    <div style="margin-top: 24px;">
        <button class="cs-btn" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Guardar cambios</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </button>
    </div>

</div>

</x-filament-panels::page>
