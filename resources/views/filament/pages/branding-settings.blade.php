<x-filament-panels::page>

<style>
.cs-card    { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 28px; max-width: 720px; }
.cs-section { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: 24px 0 12px; border-top: 1px solid #2d2d42; padding-top: 20px; }
.cs-section:first-of-type { margin-top: 0; border-top: none; padding-top: 0; }
.cs-label   { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; display: block; margin-bottom: 6px; }
.cs-input   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.cs-input:focus { border-color: {{ $primaryColor ?: '#f59e0b' }}; }
.cs-hint    { font-size: 11px; color: #64748b; margin-top: 4px; }
.cs-field   { margin-bottom: 16px; }
.cs-btn     { padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; background: {{ $primaryColor ?: '#f59e0b' }}; color: #0f0f1a; }
.cs-btn:hover { opacity: .85; }
.cs-preview { display: flex; align-items: center; gap: 16px; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 14px; margin-bottom: 10px; }
.cs-preview img { max-height: 56px; max-width: 160px; object-fit: contain; }
.cs-file    { color: #94a3b8; font-size: 13px; }
.cs-color-swatch { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #2d2d42; flex-shrink: 0; }
</style>

<div class="cs-card">

    @if($saveMessage)
    <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 10px 14px; color: #4ade80; font-size: 13px; margin-bottom: 20px;">
        {{ $saveMessage }}
    </div>
    @endif

    <div class="cs-section">Logo</div>

    @if($currentLogoUrl)
    <div class="cs-preview">
        <img src="{{ $currentLogoUrl }}" alt="Logo actual">
        <span class="cs-file">Logo actual</span>
    </div>
    @endif

    <div class="cs-field">
        <label class="cs-label">Subir logo nuevo (PNG con fondo transparente recomendado)</label>
        <input type="file" wire:model="logoUpload" accept="image/*" class="cs-input">
        @if($logoUpload)
        <div class="cs-preview" style="margin-top: 10px;">
            <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview">
            <span class="cs-file">Vista previa — se guarda al hacer clic en "Guardar cambios"</span>
        </div>
        @endif
    </div>

    <div class="cs-section">Favicon</div>

    @if($currentFaviconUrl)
    <div class="cs-preview">
        <img src="{{ $currentFaviconUrl }}" alt="Favicon actual" style="max-height: 32px;">
        <span class="cs-file">Favicon actual</span>
    </div>
    @endif

    <div class="cs-field">
        <label class="cs-label">Subir favicon nuevo (cuadrado, PNG)</label>
        <input type="file" wire:model="faviconUpload" accept="image/*" class="cs-input">
        @if($faviconUpload)
        <div class="cs-preview" style="margin-top: 10px;">
            <img src="{{ $faviconUpload->temporaryUrl() }}" alt="Preview" style="max-height: 32px;">
            <span class="cs-file">Vista previa — se guarda al hacer clic en "Guardar cambios"</span>
        </div>
        @endif
    </div>

    <div class="cs-section">Color principal</div>

    <div class="cs-field">
        <label class="cs-label">Color de acento (hex)</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="cs-color-swatch" style="background: {{ $primaryColor ?: '#f59e0b' }};"></div>
            <input type="text" class="cs-input" wire:model="primaryColor" placeholder="#E38B53" maxlength="7">
        </div>
        <p class="cs-hint">Se usa en botones, links y estados activos del panel.</p>
    </div>

    <div style="margin-top: 24px;">
        <button class="cs-btn" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Guardar cambios</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </button>
    </div>

</div>

</x-filament-panels::page>
