<x-filament-panels::page>

<style>
.bc-grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; }
@media (max-width: 900px) { .bc-grid { grid-template-columns: 1fr; } }
.bc-card { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 20px; }
.bc-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; display: block; }
.bc-input { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.bc-input:focus { border-color: #f59e0b; }
.bc-select { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
.bc-select:focus { border-color: #f59e0b; }
.bc-checkbox-list { display: flex; flex-direction: column; gap: 6px; max-height: 180px; overflow-y: auto; }
.bc-checkbox-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: background .1s; }
.bc-checkbox-item:hover { background: #23233a; }
.bc-btn { padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: opacity .15s; }
.bc-btn:disabled { opacity: .5; cursor: not-allowed; }
.bc-btn-primary { background: #f59e0b; color: #0f0f1a; }
.bc-btn-primary:hover:not(:disabled) { opacity: .85; }
.bc-btn-secondary { background: #23233a; color: #94a3b8; border: 1px solid #2d2d42; }
.bc-btn-danger { background: #dc2626; color: #fff; }
.bc-badge { display: inline-block; font-size: 10px; font-weight: 600; border-radius: 999px; padding: 2px 8px; }
.bc-table { width: 100%; border-collapse: collapse; }
.bc-table th { font-size: 11px; font-weight: 600; color: #4b5563; text-transform: uppercase; text-align: left; padding: 8px 12px; border-bottom: 1px solid #2d2d42; }
.bc-table td { font-size: 13px; color: #94a3b8; padding: 11px 12px; border-bottom: 1px solid #1e1e35; vertical-align: middle; }
.bc-table tr:last-child td { border-bottom: none; }
.bc-confirm-box { background: #13131f; border: 1px solid #f59e0b44; border-radius: 10px; padding: 16px; margin-top: 14px; }
</style>

<div class="bc-grid">

    {{-- Columna izquierda: formulario --}}
    <div class="bc-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">Nuevo broadcast</div>
            <a href="/davyt/message-templates"
               style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#94a3b8;text-decoration:none;padding:4px 10px;background:#23233a;border:1px solid #2d2d42;border-radius:6px;"
               onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#2d2d42'">
                <svg style="width:11px;height:11px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Plantillas
            </a>
        </div>

        @if($successMessage)
        <div style="background: #052e16; border: 1px solid #16a34a44; border-radius: 8px; padding: 12px 14px; color: #4ade80; font-size: 13px; margin-bottom: 16px;">
            {{ $successMessage }}
        </div>
        @endif

        {{-- Nombre --}}
        <div style="margin-bottom: 14px;">
            <label class="bc-label">Nombre del broadcast</label>
            <input
                type="text"
                class="bc-input"
                wire:model.live.debounce.300ms="broadcastName"
                placeholder="Ej: Promo julio 2026"
            >
        </div>

        {{-- Template --}}
        <div style="margin-bottom: 14px;">
            <label class="bc-label">Plantilla Meta <span style="color:#ef4444;">*</span></label>
            @if(empty($templates))
            <div style="font-size: 12px; color: #4b5563; padding: 10px; background: #13131f; border-radius: 8px; border: 1px solid #2d2d42;">
                No hay plantillas Meta activas configuradas.
            </div>
            @else
            <select class="bc-select" wire:model.live="templateId">
                <option value="">Seleccioná una plantilla...</option>
                @foreach($templates as $t)
                <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                @endforeach
            </select>
            @if($templateId)
            @php $tpl = collect($templates)->firstWhere('id', (int)$templateId); @endphp
            @if($tpl)
            <div style="margin-top: 8px; background: #13131f; border: 1px solid #2d2d42; border-radius: 6px; padding: 10px; font-size: 12px; color: #64748b; line-height: 1.5;">
                {{ $tpl['body'] }}
            </div>
            @endif
            @endif
            @endif
        </div>

        {{-- Filtro por estados --}}
        <div style="margin-bottom: 18px;">
            <label class="bc-label">Filtrar por estado <span style="color:#64748b;">(vacío = todos)</span></label>
            <div class="bc-checkbox-list">
                @foreach($statuses as $status)
                <label class="bc-checkbox-item">
                    <input
                        type="checkbox"
                        wire:model.live="filterStatusIds"
                        value="{{ $status['id'] }}"
                        style="accent-color: {{ $status['color'] ?? '#f59e0b' }}; width:14px; height:14px;"
                    >
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $status['color'] ?? '#6b7280' }}; flex-shrink: 0;"></span>
                    <span style="font-size: 13px; color: #94a3b8;">{{ $status['name'] }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Filtro por origen --}}
        @if(!empty($sources))
        <div style="margin-bottom: 18px;">
            <label class="bc-label">Filtrar por origen <span style="color:#64748b;">(vacío = todos)</span></label>
            <div class="bc-checkbox-list">
                @foreach($sources as $source)
                <label class="bc-checkbox-item">
                    <input
                        type="checkbox"
                        wire:model.live="filterSources"
                        value="{{ $source }}"
                        style="accent-color: #6366f1; width:14px; height:14px;"
                    >
                    <span style="font-size: 13px; color: #94a3b8;">{{ $source }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Límite de leads --}}
        <div style="margin-bottom: 18px;">
            <label class="bc-label">Límite de envío <span style="color:#64748b;">(0 = sin límite)</span></label>
            <input
                type="number"
                class="bc-input"
                wire:model.live="leadLimit"
                min="0"
                placeholder="Ej: 100"
                style="width: 120px;"
            >
            @if($templateId)
            <div style="margin-top: 8px; font-size: 11px; color: #475569; line-height: 1.5;">
                ✓ Se excluyen automáticamente los leads que ya recibieron esta plantilla.
            </div>
            @endif
        </div>

        {{-- Fecha/hora de inicio --}}
        <div style="margin-bottom: 18px;">
            <label class="bc-label">Fecha y hora de inicio <span style="color:#64748b;">(hora Uruguay)</span></label>
            <div style="display:flex; gap:8px;">
                <input type="date" class="bc-input" wire:model.live="startDate" style="max-width: 160px;">
                <input type="time" class="bc-input" wire:model.live="startTime" style="max-width: 110px;">
            </div>
        </div>

        {{-- Envío por tandas --}}
        <div style="margin-bottom: 18px;">
            <label class="bc-checkbox-item" style="padding: 0; margin-bottom: 8px;">
                <input type="checkbox" wire:model.live="batchEnabled" style="accent-color:#f59e0b; width:14px; height:14px;">
                <span style="font-size: 13px; color: #e2e8f0; font-weight: 600;">Repartir en tandas por día</span>
            </label>
            @if($batchEnabled)
            <div style="display:flex; align-items:center; gap:8px; margin-top: 4px;">
                <input
                    type="number"
                    class="bc-input"
                    wire:model.live="batchSize"
                    min="1"
                    style="width: 100px;"
                >
                <span style="font-size: 12px; color: #64748b;">mensajes por día, empezando en la fecha/hora de arriba</span>
            </div>
            @endif
        </div>

        {{-- Botón preview --}}
        <button
            class="bc-btn bc-btn-primary"
            wire:click="preview"
            @if(!$templateId) disabled @endif
            style="width: 100%;"
        >
            Vista previa de envío
        </button>

        {{-- Confirmación --}}
        @if($showConfirm)
        <div class="bc-confirm-box">
            <div style="font-size: 13px; color: #e2e8f0; margin-bottom: 12px;">
                Se enviarán <strong style="color:#f59e0b;">{{ $previewCount }} mensajes</strong>
                @if($previewCount === 0)
                <br><span style="color:#ef4444; font-size:12px;">No hay leads que coincidan con los filtros.</span>
                @endif
            </div>
            @if(!empty($previewSchedule))
            <div style="margin-bottom: 12px; display:flex; flex-direction:column; gap:4px; max-height: 160px; overflow-y:auto;">
                @foreach($previewSchedule as $block)
                <div style="display:flex; justify-content:space-between; font-size:12px; color:#94a3b8; background:#0f0f1a; border-radius:6px; padding:6px 10px;">
                    <span>{{ $block['date'] }}</span>
                    <span style="color:#e2e8f0; font-weight:600;">{{ $block['count'] }} mensajes</span>
                </div>
                @endforeach
            </div>
            @endif
            <div style="display: flex; gap: 8px;">
                <button
                    class="bc-btn bc-btn-danger"
                    wire:click="send"
                    @if($previewCount === 0) disabled @endif
                    wire:loading.attr="disabled"
                    wire:target="send"
                >
                    <span wire:loading.remove wire:target="send">Confirmar envío</span>
                    <span wire:loading wire:target="send">Enviando...</span>
                </button>
                <button class="bc-btn bc-btn-secondary" wire:click="cancelConfirm">Cancelar</button>
            </div>
        </div>
        @endif
    </div>

    {{-- Columna derecha: historial --}}
    <div class="bc-card">
        <div style="font-size: 15px; font-weight: 700; color: #e2e8f0; margin-bottom: 18px;">
            Historial
            <span style="font-size: 12px; font-weight: 400; color: #4b5563; margin-left: 6px;">(últimos 20)</span>
        </div>

        @if(empty($history))
        <div style="padding: 48px 0; text-align: center; color: #374151; font-size: 13px;">
            Aún no enviaste ningún broadcast.
        </div>
        @else
        <div style="overflow-x: auto;">
        <table class="bc-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Plantilla</th>
                    <th>Total</th>
                    <th>Enviados</th>
                    <th>Fallidos</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $b)
                @php
                    $sent   = $b['sent_count'];
                    $failed = $b['failed_count'];
                    $total  = $b['total_count'];
                    $pct    = $total > 0 ? round(($sent / $total) * 100) : 0;
                @endphp
                <tr>
                    <td style="color: #e2e8f0; font-weight: 500; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $b['name'] }}
                    </td>
                    <td style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $b['message_template']['name'] ?? '—' }}
                    </td>
                    <td>{{ $total }}</td>
                    <td>
                        <span class="bc-badge" style="background:#052e16; color:#4ade80;">{{ $sent }}</span>
                    </td>
                    <td>
                        @if($failed > 0)
                        <span class="bc-badge" style="background:#450a0a; color:#f87171;">{{ $failed }}</span>
                        @else
                        <span style="color:#374151;">0</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        {{ \Carbon\Carbon::parse($b['created_at'])->format('d/m/Y H:i') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

</div>

</x-filament-panels::page>
