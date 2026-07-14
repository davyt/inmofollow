<x-filament-widgets::widget>

@php
function clf_color(string $label): array {
    $l = mb_strtolower($label);
    if (str_contains($l, 'interesado') || str_contains($l, 'consulta') || str_contains($l, 'quiere')) {
        return ['bar' => '#22c55e', 'bg' => '#22c55e18', 'text' => '#4ade80'];
    }
    if (str_contains($l, 'no interesado') || str_contains($l, 'descart') || str_contains($l, 'no contact')) {
        return ['bar' => '#ef4444', 'bg' => '#ef444418', 'text' => '#f87171'];
    }
    if (str_contains($l, 'automát') || str_contains($l, 'bot') || str_contains($l, 'empresa')) {
        return ['bar' => '#6366f1', 'bg' => '#6366f118', 'text' => '#a5b4fc'];
    }
    if (str_contains($l, 'sin_respuesta') || str_contains($l, 'sin respuesta')) {
        return ['bar' => '#334155', 'bg' => '#33415520', 'text' => '#64748b'];
    }
    // neutral / indefinido
    return ['bar' => '#f59e0b', 'bg' => '#f59e0b18', 'text' => '#fbbf24'];
}
@endphp

<style>
.clf2-wrap       { padding: 4px 0; }
.clf2-head       { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 20px; }
.clf2-title      { font-size: 13px; font-weight: 700; color: #94a3b8; letter-spacing: .06em; text-transform: uppercase; }
.clf2-meta       { font-size: 12px; color: #475569; }
.clf2-grid       { display: grid; gap: 10px; }
.clf2-card       { border-radius: 8px; padding: 12px 14px; display: flex; align-items: center; gap: 14px; }
.clf2-label      { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.clf2-bar-wrap   { flex: 0 0 200px; height: 10px; background: #1e293b; border-radius: 5px; overflow: hidden; }
@media (max-width: 640px) { .clf2-bar-wrap { flex: 0 0 90px; } }
.clf2-bar        { height: 100%; border-radius: 5px; transition: width .4s ease; }
.clf2-count      { font-size: 20px; font-weight: 800; width: 42px; text-align: right; flex-shrink: 0; line-height: 1; }
.clf2-pct        { font-size: 12px; color: #475569; width: 36px; text-align: right; flex-shrink: 0; }
.clf2-empty      { color: #64748b; font-size: 13px; padding: 20px 0; }
.clf2-footer     { margin-top: 16px; font-size: 11px; color: #334155; font-style: italic; }
</style>

<div class="clf2-wrap">
    <div class="clf2-head">
        <span class="clf2-title">Clasificación de contactos</span>
        @if($total > 0)
            <span class="clf2-meta">{{ $total }} clasificados &middot; {{ $pending }} sin clasificar</span>
        @endif
    </div>

    @if($rows->isEmpty())
        <p class="clf2-empty">Todavía no hay clasificaciones. Se irán acumulando a medida que los contactos respondan.</p>
    @else
        <div class="clf2-grid">
            @foreach($rows as $row)
                @php
                    $isNoResp = $row->ai_classification === 'sin_respuesta';
                    $labelText = $isNoResp ? 'Sin respuesta' : $row->ai_classification;
                    $pct = $total > 0 ? round($row->count / $total * 100) : 0;
                    $c = clf_color($row->ai_classification);
                @endphp
                <div class="clf2-card" style="background: {{ $c['bg'] }}; border: 1px solid {{ $c['bar'] }}22;">
                    <span class="clf2-label" style="color: {{ $c['text'] }};" title="{{ $labelText }}">
                        {{ $labelText }}
                    </span>
                    <div class="clf2-bar-wrap">
                        <div class="clf2-bar" style="width: {{ $pct }}%; background: {{ $c['bar'] }};"></div>
                    </div>
                    <span class="clf2-count" style="color: {{ $c['text'] }};">{{ $row->count }}</span>
                    <span class="clf2-pct">{{ $pct }}%</span>
                </div>
            @endforeach
        </div>

        @if($pending > 0)
        <p class="clf2-footer">{{ $pending }} lead{{ $pending !== 1 ? 's' : '' }} aún sin clasificar — el cron nocturno los procesa automáticamente.</p>
        @endif
    @endif
</div>

</x-filament-widgets::widget>
