<div wire:poll.20s="loadInbox" style="display: flex; height: calc(100vh - 130px); gap: 0; border-radius: 12px; overflow: hidden; border: 1px solid #2d2d42;">

    {{-- Panel izquierdo: lista de conversaciones --}}
    <div style="width: 320px; flex-shrink: 0; display: flex; flex-direction: column; background: #1a1a2e; border-right: 1px solid #2d2d42;">

        {{-- Header --}}
        <div style="padding: 16px; border-bottom: 1px solid #2d2d42;">
            <div style="font-size: 13px; font-weight: 600; color: #94a3b8;">
                {{ count($conversations) }} conversación{{ count($conversations) !== 1 ? 'es' : '' }}
            </div>
        </div>

        {{-- Lista --}}
        <div style="flex: 1; overflow-y: auto;">
            @forelse($conversations as $conv)
            @php
                $initials = collect(explode(' ', $conv['name']))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                $isSelected = $selectedLeadId === $conv['id'];
                $timeAgo = $conv['last_at'] ? \Carbon\Carbon::parse($conv['last_at'])->diffForHumans(short: true) : '';
            @endphp
            <div
                wire:click="selectLead({{ $conv['id'] }})"
                style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #1e1e35; transition: background .1s;
                    {{ $isSelected ? 'background: #23233a;' : '' }}"
                onmouseover="if(!{{ $isSelected ? 'true' : 'false' }}) this.style.background='#1e1e35'"
                onmouseout="if(!{{ $isSelected ? 'true' : 'false' }}) this.style.background=''"
            >
                {{-- Avatar --}}
                <div style="width: 40px; height: 40px; border-radius: 50%; background: {{ $conv['status_color'] }}22; border: 2px solid {{ $conv['status_color'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; font-weight: 700; color: {{ $conv['status_color'] }};">
                    {{ $initials }}
                </div>

                {{-- Contenido --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                        <span style="font-size: 13px; font-weight: {{ $conv['unread'] ? '700' : '500' }}; color: {{ $conv['unread'] ? '#e2e8f0' : '#94a3b8' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px;">
                            {{ $conv['name'] }}
                        </span>
                        <span style="font-size: 11px; color: #4b5563; flex-shrink: 0; margin-left: 4px;">{{ $timeAgo }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        @if($conv['direction'] === 'out')
                        <span style="color: #34d399; font-size: 11px; flex-shrink: 0;">↗</span>
                        @else
                        <span style="color: #60a5fa; font-size: 11px; flex-shrink: 0;">↙</span>
                        @endif
                        <span style="font-size: 12px; color: #4b5563; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $conv['last_message'] ?: '...' }}
                        </span>
                    </div>
                    @if($conv['unread'])
                    <div style="margin-top: 4px;">
                        <span style="font-size: 10px; background: #1d4ed8; color: #93c5fd; border-radius: 999px; padding: 1px 7px; font-weight: 600;">No respondido</span>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div style="padding: 48px 24px; text-align: center; color: #374151; font-size: 13px;">
                No hay conversaciones todavía.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Panel derecho: conversación activa --}}
    <div style="flex: 1; display: flex; flex-direction: column; background: #13131f; min-width: 0;">

        @if($selectedLeadId && $this->selectedLead)
        @php $lead = $this->selectedLead; @endphp

        {{-- Header del lead --}}
        <div style="padding: 14px 20px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; justify-content: space-between; background: #1a1a2e;">
            <div>
                <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">{{ $lead->name }}</div>
                <div style="font-size: 12px; color: #4b5563; margin-top: 1px;">{{ $lead->phone }}</div>
            </div>
            <a href="/davyt/leads/{{ $lead->id }}/edit"
               style="font-size: 12px; color: #f59e0b; text-decoration: none; padding: 5px 12px; border: 1px solid #f59e0b33; border-radius: 6px;">
                Ver lead →
            </a>
        </div>

        {{-- Conversación embebida --}}
        <div style="flex: 1; overflow-y: auto; padding: 16px 20px;">
            @livewire('lead-conversation', ['lead' => $lead], key($selectedLeadId))
        </div>

        @else

        {{-- Empty state --}}
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #374151;">
            <div style="font-size: 40px; margin-bottom: 12px;">💬</div>
            <div style="font-size: 14px; font-weight: 500; color: #4b5563;">Seleccioná una conversación</div>
            <div style="font-size: 12px; color: #374151; margin-top: 4px;">Hacé clic en un contacto de la lista</div>
        </div>

        @endif
    </div>
</div>
