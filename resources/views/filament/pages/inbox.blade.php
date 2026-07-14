<x-filament-panels::page>

<style>
.inbox-wrap      { display: flex; height: calc(100vh - 200px); border-radius: 12px; overflow: hidden; border: 1px solid #2d2d42; }
.inbox-list      { width: 320px; flex-shrink: 0; display: flex; flex-direction: column; background: #1a1a2e; border-right: 1px solid #2d2d42; }
.inbox-detail    { flex: 1; display: flex; flex-direction: column; background: #13131f; min-width: 0; }

@media (max-width: 768px) {
    .inbox-wrap                        { height: calc(100vh - 160px); }
    .inbox-list                        { width: 100%; border-right: none; }
    .inbox-list.mobile-hidden          { display: none; }
    .inbox-detail                      { display: none; }
    .inbox-detail.mobile-visible       { display: flex; }
    .inbox-back                        { display: flex !important; }
}
@media (min-width: 769px) {
    .inbox-back { display: none !important; }
    .inbox-list, .inbox-detail { display: flex !important; }
}
</style>

<div
    wire:poll.20s="loadInbox"
    x-data="{ showDetail: false }"
    class="inbox-wrap"
>
    {{-- Panel izquierdo: lista --}}
    <div class="inbox-list" :class="showDetail ? 'mobile-hidden' : ''">

        <div style="padding: 16px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <span style="font-size: 13px; font-weight: 600; color: #94a3b8;">
                {{ count($conversations) }} conversación{{ count($conversations) !== 1 ? 'es' : '' }}
            </span>
            <button
                wire:click="openNewConversation"
                style="display: flex; align-items: center; gap: 4px; background: #f59e0b; color: #1a1a2e; border: none; border-radius: 6px; padding: 5px 10px; font-size: 12px; font-weight: 700; cursor: pointer;"
            >+ Nueva</button>
        </div>

        <div style="flex: 1; overflow-y: auto;">
            @forelse($conversations as $conv)
            @php
                $initials   = collect(explode(' ', $conv['name']))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                $isSelected = $selectedLeadId === $conv['id'];
                $timeAgo    = $conv['last_at'] ? \Carbon\Carbon::parse($conv['last_at'])->diffForHumans(short: true) : '';
            @endphp
            <div
                wire:click="selectLead({{ $conv['id'] }})"
                x-on:click="showDetail = true"
                style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #1e1e35; transition: background .1s; {{ $isSelected ? 'background:#23233a;' : '' }}"
                onmouseover="this.style.background='#1e1e35'"
                onmouseout="this.style.background='{{ $isSelected ? '#23233a' : '' }}'"
            >
                <div style="width: 40px; height: 40px; border-radius: 50%; background: {{ $conv['status_color'] }}22; border: 2px solid {{ $conv['status_color'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; font-weight: 700; color: {{ $conv['status_color'] }};">
                    {{ $initials }}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                        <span style="font-size: 13px; font-weight: {{ $conv['unread'] ? '700' : '500' }}; color: {{ $conv['unread'] ? '#e2e8f0' : '#94a3b8' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px;">
                            {{ $conv['name'] }}
                        </span>
                        <span style="font-size: 11px; color: #4b5563; flex-shrink: 0; margin-left: 6px;">{{ $timeAgo }}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <span style="font-size: 11px; flex-shrink: 0; color: {{ $conv['direction'] === 'out' ? '#34d399' : '#60a5fa' }};">
                            {{ $conv['direction'] === 'out' ? '↗' : '↙' }}
                        </span>
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
            @if($hasMore)
            <div style="padding: 12px 14px;">
                <button
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    style="width: 100%; background: none; border: 1px solid #2d2d42; color: #94a3b8; border-radius: 6px; padding: 8px; font-size: 12px; cursor: pointer;"
                >Cargar más</button>
            </div>
            @endif
        </div>
    </div>

    {{-- Panel derecho: conversación --}}
    <div class="inbox-detail" :class="showDetail ? 'mobile-visible' : ''">

        @if($selectedLeadId && $this->selectedLead)
        @php $lead = $this->selectedLead; @endphp

        {{-- Header --}}
        <div style="padding: 12px 16px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; gap: 10px; background: #1a1a2e;">
            {{-- Botón atrás (solo móvil) --}}
            <button
                class="inbox-back"
                x-on:click="showDetail = false"
                style="display: none; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px 8px; font-size: 18px; line-height: 1;"
            >←</button>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $lead->name }}</div>
                <div style="font-size: 11px; color: #4b5563;">{{ $lead->phone }}</div>
            </div>
            @if($lead->primaryListing?->listing_url)
            <a href="{{ $lead->primaryListing->listing_url }}" target="_blank" rel="noopener"
               title="{{ $lead->primaryListing->title }}"
               style="font-size: 12px; color: #34d399; text-decoration: none; padding: 5px 10px; border: 1px solid #34d39944; border-radius: 6px; white-space: nowrap; flex-shrink: 0;">
                Ver publicación →
            </a>
            @endif
            <a href="/davyt/leads/{{ $lead->id }}/edit"
               style="font-size: 12px; color: #f59e0b; text-decoration: none; padding: 5px 10px; border: 1px solid #f59e0b44; border-radius: 6px; white-space: nowrap; flex-shrink: 0;">
                Ver lead →
            </a>
        </div>

        {{-- Conversación --}}
        <div style="flex: 1; overflow-y: auto; padding: 16px;">
            @livewire('lead-conversation', ['lead' => $lead], key($selectedLeadId))
        </div>

        @else

        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #374151;">
            <div style="font-size: 36px; margin-bottom: 10px;">💬</div>
            <div style="font-size: 14px; font-weight: 500; color: #4b5563;">Seleccioná una conversación</div>
            <div style="font-size: 12px; color: #374151; margin-top: 4px;">Hacé clic en un contacto de la lista</div>
        </div>

        @endif
    </div>

    {{-- Modal: nueva conversación --}}
    @if($showNewConversation)
    <div
        wire:click="closeNewConversation"
        style="position: fixed; inset: 0; background: #00000099; z-index: 40; display: flex; align-items: flex-start; justify-content: center; padding-top: 10vh;"
    >
        <div
            wire:click.stop
            style="width: 100%; max-width: 420px; background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; overflow: hidden; box-shadow: 0 12px 32px #00000066;"
        >
            <div style="padding: 14px 16px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 14px; font-weight: 700; color: #e2e8f0;">Nueva conversación</span>
                <button wire:click="closeNewConversation" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 18px; line-height: 1;">×</button>
            </div>
            <div style="padding: 12px 16px;">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="leadSearch"
                    placeholder="Buscar lead por nombre o teléfono..."
                    autofocus
                    style="width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 6px; padding: 8px 10px; font-size: 13px; color: #e2e8f0; outline: none;"
                >
            </div>
            <div style="max-height: 320px; overflow-y: auto; padding-bottom: 8px;">
                @forelse($leadSearchResults as $r)
                <div
                    wire:click="startConversation({{ $r['id'] }})"
                    x-on:click="showDetail = true"
                    style="padding: 10px 16px; cursor: pointer; display: flex; flex-direction: column; gap: 1px;"
                    onmouseover="this.style.background='#1e1e35'"
                    onmouseout="this.style.background='transparent'"
                >
                    <span style="font-size: 13px; font-weight: 600; color: #e2e8f0;">{{ $r['name'] ?: 'Sin nombre' }}</span>
                    <span style="font-size: 11px; color: #6b7280;">{{ $r['phone'] ?: 'Sin teléfono' }}</span>
                </div>
                @empty
                <div style="padding: 16px; text-align: center; color: #374151; font-size: 12px;">
                    {{ mb_strlen(trim($leadSearch)) < 2 ? 'Escribí al menos 2 caracteres.' : 'Sin resultados.' }}
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>

</x-filament-panels::page>
