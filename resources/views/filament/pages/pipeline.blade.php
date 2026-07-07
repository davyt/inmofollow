<div
    x-data="{ dragging: null, dropTarget: null }"
    style="overflow-x: auto; padding-bottom: 24px;"
>
    <div style="display: flex; gap: 14px; min-width: max-content; min-height: calc(100vh - 200px); padding: 4px 2px;">

        @forelse($statuses as $status)
        @php $statusColor = $status['color'] ?? '#6b7280'; @endphp
        <div
            style="width: 272px; flex-shrink: 0; display: flex; flex-direction: column; background: #1a1a2e; border-radius: 12px; border: 1px solid #2d2d42; overflow: hidden; transition: box-shadow .15s;"
            :style="dropTarget === {{ $status['id'] }} ? 'box-shadow: 0 0 0 2px {{ $statusColor }}; border-color: {{ $statusColor }};' : ''"
            @dragover.prevent="dropTarget = {{ $status['id'] }}"
            @dragleave="dropTarget = null"
            @drop.prevent="
                if (dragging !== null) { $wire.moveLead(dragging, {{ $status['id'] }}); }
                dragging = null; dropTarget = null;
            "
        >
            {{-- Barra de color superior --}}
            <div style="height: 4px; background: {{ $statusColor }};"></div>

            {{-- Cabecera --}}
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid #2d2d42;">
                <span style="font-weight: 600; font-size: 13px; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                    {{ $status['name'] }}
                </span>
                <span style="font-size: 11px; font-weight: 500; background: #2d2d42; color: #94a3b8; border-radius: 999px; padding: 2px 8px; flex-shrink: 0; margin-left: 8px;">
                    {{ count($leadsByStatus[$status['id']] ?? []) }}
                </span>
            </div>

            {{-- Lista de cards --}}
            <div style="flex: 1; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 8px; max-height: calc(100vh - 310px);">

                @forelse(($leadsByStatus[$status['id']] ?? []) as $lead)
                <div
                    wire:key="lead-{{ $lead['id'] }}"
                    draggable="true"
                    @dragstart="dragging = {{ $lead['id'] }}"
                    @dragend="dragging = null; dropTarget = null"
                    :style="dragging === {{ $lead['id'] }} ? 'opacity: .4; transform: scale(.97);' : ''"
                    style="background: #23233a; border: 1px solid #33334d; border-radius: 8px; padding: 12px; cursor: grab; transition: box-shadow .12s, transform .12s; user-select: none;"
                    onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.35)'"
                    onmouseout="this.style.boxShadow='none'"
                >
                    {{-- Nombre --}}
                    <a href="/davyt/leads/{{ $lead['id'] }}/edit"
                       style="display: block; font-weight: 600; font-size: 13px; color: #e2e8f0; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;"
                       @click.stop
                    >{{ $lead['name'] ?? 'Sin nombre' }}</a>

                    {{-- Teléfono --}}
                    @if(!empty($lead['phone']))
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                        {{ $lead['phone'] }}
                    </div>
                    @endif

                    {{-- Tags --}}
                    @if(!empty($lead['property_type']) || !empty($lead['zone']))
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                        @if(!empty($lead['property_type']))
                        <span style="font-size: 11px; background: #1e3a5f; color: #60a5fa; border-radius: 4px; padding: 2px 7px;">
                            {{ $lead['property_type'] }}
                        </span>
                        @endif
                        @if(!empty($lead['zone']))
                        <span style="font-size: 11px; background: #2d1f4e; color: #a78bfa; border-radius: 4px; padding: 2px 7px;">
                            {{ $lead['zone'] }}
                        </span>
                        @endif
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #2d2d42;">
                        <span style="font-size: 11px; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                            @if(!auth()->user()->isAgent())
                                {{ $lead['user']['name'] ?? 'Sin agente' }}
                            @else
                                {{ $lead['source'] ?? '—' }}
                            @endif
                        </span>

                        @if(!empty($lead['next_follow_up_at']))
                        @php
                            $fu = \Carbon\Carbon::parse($lead['next_follow_up_at']);
                            [$bg, $fg] = $fu->isPast()
                                ? ['#450a0a', '#f87171']
                                : ($fu->isToday()
                                    ? ['#451a03', '#fb923c']
                                    : ['#052e16', '#4ade80']);
                        @endphp
                        <span style="font-size: 11px; background: {{ $bg }}; color: {{ $fg }}; border-radius: 4px; padding: 2px 7px; flex-shrink: 0;">
                            {{ $fu->format('d/m') }}
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div
                    style="border: 2px dashed #2d2d42; border-radius: 8px; padding: 28px 16px; text-align: center; color: #374151; font-size: 12px; transition: border-color .15s, color .15s;"
                    :style="dropTarget === {{ $status['id'] }} ? 'border-color: {{ $statusColor }}; color: #94a3b8;' : ''"
                >
                    Arrastra un lead aquí
                </div>
                @endforelse

            </div>
        </div>
        @empty
        <div style="display: flex; align-items: center; justify-content: center; width: 100%; color: #4b5563; font-size: 14px;">
            No hay estados definidos. Crea estados en Configuración → Estados de leads.
        </div>
        @endforelse

    </div>
</div>
