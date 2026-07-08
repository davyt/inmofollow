<x-filament-panels::page>
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
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #2d2d42;">
                <span style="font-weight: 600; font-size: 13px; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px;">
                    {{ $status['name'] }}
                </span>
                <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: 6px;">
                    <span style="font-size: 11px; font-weight: 500; background: #2d2d42; color: #94a3b8; border-radius: 999px; padding: 2px 8px;">
                        {{ count($leadsByStatus[$status['id']] ?? []) }}
                    </span>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                    <button
                        wire:click="openEditStatus({{ $status['id'] }})"
                        title="Configurar"
                        style="background: none; border: none; cursor: pointer; color: {{ $editingStatusId === $status['id'] ? '#f59e0b' : '#4b5563' }}; padding: 2px; line-height: 0; transition: color .15s;"
                        onmouseover="this.style.color='#f59e0b'" onmouseout="this.style.color='{{ $editingStatusId === $status['id'] ? '#f59e0b' : '#4b5563' }}'"
                    >
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Panel de configuración inline --}}
            @if($editingStatusId === $status['id'])
            <div style="padding: 12px; border-bottom: 1px solid #2d2d42; background: #13131f; display: flex; flex-direction: column; gap: 10px;">

                {{-- Nombre --}}
                <input
                    type="text"
                    wire:model="editStatusName"
                    wire:keydown.enter="saveStatus"
                    wire:keydown.escape="$set('editingStatusId', null)"
                    placeholder="Nombre del estado"
                    style="width: 100%; background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 6px; padding: 7px 10px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box;"
                >

                {{-- Paleta de colores --}}
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    @foreach(['#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#ec4899','#f97316','#14b8a6','#64748b'] as $c)
                    <button
                        type="button"
                        wire:click="$set('editStatusColor', '{{ $c }}')"
                        style="width: 20px; height: 20px; border-radius: 50%; background: {{ $c }}; border: {{ $editStatusColor === $c ? '2px solid #fff' : '2px solid transparent' }}; cursor: pointer; transition: transform .1s;"
                        onmouseover="this.style.transform='scale(1.25)'" onmouseout="this.style.transform='scale(1)'"
                    ></button>
                    @endforeach
                </div>

                {{-- Acciones --}}
                <div style="display: flex; gap: 6px; align-items: center;">
                    <button wire:click="saveStatus"
                        style="flex:1; padding: 6px; background: #f59e0b; color: #0f0f1a; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                        Guardar
                    </button>
                    <button wire:click="moveStatus({{ $status['id'] }}, 'left')" title="Mover izquierda"
                        style="padding: 6px 8px; background: #23233a; color: #94a3b8; border: 1px solid #2d2d42; border-radius: 6px; font-size: 12px; cursor: pointer;">
                        ←
                    </button>
                    <button wire:click="moveStatus({{ $status['id'] }}, 'right')" title="Mover derecha"
                        style="padding: 6px 8px; background: #23233a; color: #94a3b8; border: 1px solid #2d2d42; border-radius: 6px; font-size: 12px; cursor: pointer;">
                        →
                    </button>
                    <button
                        wire:click="deleteStatus({{ $status['id'] }})"
                        wire:confirm="¿Eliminar '{{ $status['name'] }}'? Los leads de esta columna quedarán sin estado asignado."
                        title="Eliminar"
                        style="padding: 6px 8px; background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; border-radius: 6px; font-size: 12px; cursor: pointer;">
                        🗑
                    </button>
                </div>
            </div>
            @endif

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
        {{-- mensaje vacío se muestra debajo del botón de añadir --}}
        @endforelse

        {{-- Columna: Añadir nuevo estado --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
        <div style="width: 240px; flex-shrink: 0; display: flex; flex-direction: column;">

            @if(!$showNewStatus)

            <button
                wire:click="$set('showNewStatus', true)"
                style="width: 100%; height: 56px; background: transparent; border: 2px dashed #2d2d42; border-radius: 12px; color: #4b5563; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: border-color .15s, color .15s;"
                onmouseover="this.style.borderColor='#f59e0b';this.style.color='#f59e0b'"
                onmouseout="this.style.borderColor='#2d2d42';this.style.color='#4b5563'"
            >
                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Añadir estado
            </button>

            @else

            <div style="background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                <div style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em;">Nuevo estado</div>

                <input
                    type="text"
                    wire:model="newStatusName"
                    placeholder="Nombre del estado"
                    autofocus
                    style="width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 7px; padding: 8px 10px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box;"
                    wire:keydown.enter="createStatus"
                    wire:keydown.escape="$set('showNewStatus', false)"
                >

                {{-- Paleta de colores --}}
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    @foreach(['#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#ec4899','#f97316','#14b8a6','#64748b'] as $color)
                    <button
                        type="button"
                        wire:click="$set('newStatusColor', '{{ $color }}')"
                        style="width: 22px; height: 22px; border-radius: 50%; background: {{ $color }}; border: {{ $newStatusColor === $color ? '2px solid #fff' : '2px solid transparent' }}; cursor: pointer; flex-shrink: 0; transition: transform .1s;"
                        onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"
                    ></button>
                    @endforeach
                </div>

                <div style="display: flex; gap: 6px;">
                    <button
                        wire:click="createStatus"
                        style="flex: 1; padding: 7px; background: #f59e0b; color: #0f0f1a; border: none; border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer;"
                    >Crear</button>
                    <button
                        wire:click="$set('showNewStatus', false)"
                        style="padding: 7px 12px; background: #23233a; color: #94a3b8; border: 1px solid #2d2d42; border-radius: 7px; font-size: 12px; cursor: pointer;"
                    >✕</button>
                </div>
            </div>

            @endif
        </div>
        @endif

        @if(empty($statuses) && !$showNewStatus)
        <div style="display: flex; align-items: center; justify-content: center; width: 100%; color: #4b5563; font-size: 14px;">
            No hay estados todavía. Creá el primero con el botón de arriba.
        </div>
        @endif

    </div>
</div>
</x-filament-panels::page>
