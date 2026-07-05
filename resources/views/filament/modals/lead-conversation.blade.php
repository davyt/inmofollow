<div style="display: flex; flex-direction: column; gap: 4px; max-height: 65vh; overflow-y: auto; padding: 4px;">

    @forelse ($conversation as $item)
        @php
            $isOut  = $item['direction'] === 'out';
            $failed = $isOut && ($item['status'] ?? '') === 'failed';
            $date   = $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') : '-';

            $bubbleColor = match (true) {
                $failed => 'rgba(239,68,68,.12)',
                $isOut  => 'rgba(52,211,153,.14)',
                default => 'rgba(255,255,255,.06)',
            };

            $borderColor = match (true) {
                $failed => '#ef4444',
                $isOut  => '#34d399',
                default => '#4b5563',
            };
        @endphp

        <div style="display: flex; justify-content: {{ $isOut ? 'flex-end' : 'flex-start' }};">
            <div style="max-width: 75%; background: {{ $bubbleColor }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 10px 14px; margin: 4px 0;">
                <div style="color: #e5e7eb; font-size: 14px; line-height: 1.5; white-space: pre-line; word-break: break-word;">
                    {{ $item['text'] ?: '-' }}
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 6px; margin-top: 6px;">
                    @if ($failed)
                        <span style="font-size: 11px; color: #f87171; font-weight: 600;">No se pudo enviar</span>
                    @endif
                    <span style="font-size: 11px; color: #9ca3af;">{{ $date }}</span>
                </div>
            </div>
        </div>
    @empty
        <div style="color: #9ca3af; text-align: center; padding: 32px 0;">
            Todavía no hay mensajes de WhatsApp con este lead.
        </div>
    @endforelse

</div>
