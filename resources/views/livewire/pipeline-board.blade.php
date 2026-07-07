<div
    x-data="{
        dragging: null,
        dropTarget: null,
    }"
    class="overflow-x-auto"
>
    <div class="flex gap-4 pb-6" style="min-width: max-content; min-height: calc(100vh - 260px);">

        @forelse($statuses as $status)
        <div
            class="flex flex-col rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 w-72 flex-shrink-0 transition-all"
            :class="dropTarget === {{ $status['id'] }} ? 'ring-2 ring-amber-400 border-amber-400' : ''"
            @dragover.prevent="dropTarget = {{ $status['id'] }}"
            @dragleave="dropTarget = null"
            @drop.prevent="
                if (dragging !== null) {
                    $wire.moveLead(dragging, {{ $status['id'] }});
                }
                dragging = null;
                dropTarget = null;
            "
        >
            {{-- Cabecera de columna --}}
            <div class="flex items-center gap-2 px-3 py-3 border-b border-gray-200 dark:border-gray-700">
                <div
                    class="w-3 h-3 rounded-full flex-shrink-0"
                    style="background-color: {{ $status['color'] ?? '#6b7280' }};"
                ></div>
                <span class="font-semibold text-sm text-gray-700 dark:text-gray-200 flex-1 truncate">
                    {{ $status['name'] }}
                </span>
                <span class="text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-full px-2 py-0.5 flex-shrink-0">
                    {{ count($leadsByStatus[$status['id']] ?? []) }}
                </span>
            </div>

            {{-- Cards --}}
            <div class="flex-1 overflow-y-auto p-2 flex flex-col gap-2" style="max-height: calc(100vh - 340px);">
                @forelse(($leadsByStatus[$status['id']] ?? []) as $lead)
                <div
                    wire:key="lead-{{ $lead['id'] }}"
                    draggable="true"
                    @dragstart="dragging = {{ $lead['id'] }}"
                    @dragend="dragging = null; dropTarget = null"
                    :class="dragging === {{ $lead['id'] }} ? 'opacity-40 scale-95' : 'hover:shadow-md'"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-grab active:cursor-grabbing shadow-sm transition-all select-none"
                >
                    {{-- Nombre --}}
                    <div class="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">
                        {{ $lead['name'] ?? 'Sin nombre' }}
                    </div>

                    {{-- Teléfono --}}
                    @if (!empty($lead['phone']))
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $lead['phone'] }}
                    </div>
                    @endif

                    {{-- Tags: tipo y zona --}}
                    @if (!empty($lead['property_type']) || !empty($lead['zone']))
                    <div class="flex flex-wrap gap-1 mt-2">
                        @if (!empty($lead['property_type']))
                        <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded px-1.5 py-0.5">
                            {{ $lead['property_type'] }}
                        </span>
                        @endif
                        @if (!empty($lead['zone']))
                        <span class="text-xs bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded px-1.5 py-0.5">
                            {{ $lead['zone'] }}
                        </span>
                        @endif
                    </div>
                    @endif

                    {{-- Footer: agente + próximo seguimiento --}}
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <div class="text-xs text-gray-400 dark:text-gray-500 truncate flex-1 min-w-0 mr-1">
                            @if (!auth()->user()->isAgent())
                                {{ $lead['user']['name'] ?? 'Sin agente' }}
                            @else
                                {{ $lead['source'] ?? '' }}
                            @endif
                        </div>

                        @if (!empty($lead['next_follow_up_at']))
                        @php
                            $followUp   = \Carbon\Carbon::parse($lead['next_follow_up_at']);
                            $colorClass = $followUp->isPast()
                                ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                : ($followUp->isToday()
                                    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'
                                    : 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400');
                        @endphp
                        <span class="text-xs rounded px-1.5 py-0.5 flex-shrink-0 {{ $colorClass }}">
                            {{ $followUp->format('d/m') }}
                        </span>
                        @endif
                    </div>

                    {{-- Acción: ir al lead --}}
                    <a
                        href="/davyt/leads/{{ $lead['id'] }}/edit"
                        class="mt-2 w-full text-center block text-xs text-gray-400 dark:text-gray-600 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                        @click.stop
                    >
                        Ver detalle →
                    </a>
                </div>
                @empty
                <div class="flex items-center justify-center h-20 text-xs text-gray-400 dark:text-gray-600 italic">
                    Sin leads
                </div>
                @endforelse
            </div>
        </div>
        @empty
        <div class="flex items-center justify-center w-full text-gray-400 dark:text-gray-600">
            No hay estados definidos. Crea estados en Configuración → Estados.
        </div>
        @endforelse

    </div>
</div>
