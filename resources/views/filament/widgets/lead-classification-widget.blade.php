<x-filament-widgets::widget>

<style>
.clf-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 14px;
}
.clf-title {
    font-size: 14px;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.clf-total {
    font-size: 12px;
    color: #64748b;
}
.clf-empty {
    color: #64748b;
    font-size: 13px;
    padding: 12px 0;
}
.clf-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 9px;
}
.clf-label {
    font-size: 13px;
    color: #cbd5e1;
    min-width: 0;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.clf-label.no-response {
    color: #64748b;
}
.clf-bar-wrap {
    width: 120px;
    height: 8px;
    background: #1e293b;
    border-radius: 4px;
    flex-shrink: 0;
    overflow: hidden;
}
.clf-bar {
    height: 100%;
    border-radius: 4px;
    background: #f59e0b;
    transition: width .3s;
}
.clf-bar.no-response {
    background: #334155;
}
.clf-count {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    width: 28px;
    text-align: right;
    flex-shrink: 0;
}
.clf-pct {
    font-size: 11px;
    color: #475569;
    width: 32px;
    text-align: right;
    flex-shrink: 0;
}
.clf-phase {
    margin-top: 12px;
    font-size: 11px;
    color: #475569;
    font-style: italic;
}
</style>

<div>
    <div class="clf-header">
        <span class="clf-title">Clasificación de contactos</span>
        @if($total > 0)
            <span class="clf-total">{{ $total }} clasificados · {{ $pending }} sin clasificar</span>
        @endif
    </div>

    @if($rows->isEmpty())
        <p class="clf-empty">Todavía no hay clasificaciones. Se irán acumulando a medida que los contactos respondan y el cron detecte los que no responden.</p>
    @else
        @foreach($rows as $row)
            @php
                $isNoResponse = $row->ai_classification === 'sin_respuesta';
                $pct = $total > 0 ? round($row->count / $total * 100) : 0;
            @endphp
            <div class="clf-row">
                <span class="clf-label {{ $isNoResponse ? 'no-response' : '' }}">
                    {{ $isNoResponse ? '— sin respuesta' : $row->ai_classification }}
                </span>
                <div class="clf-bar-wrap">
                    <div class="clf-bar {{ $isNoResponse ? 'no-response' : '' }}" style="width: {{ $pct }}%"></div>
                </div>
                <span class="clf-count">{{ $row->count }}</span>
                <span class="clf-pct">{{ $pct }}%</span>
            </div>
        @endforeach

        <p class="clf-phase">Fase 1 — recolección libre. En ~3 semanas se agrupan en categorías definitivas.</p>
    @endif
</div>

</x-filament-widgets::widget>
