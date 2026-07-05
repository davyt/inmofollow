<div style="display: flex; flex-direction: column; gap: 20px;">

    {{-- Lead info card --}}
    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 13px; color: #9ca3af; margin-bottom: 8px;">Lead / Propietario</div>
        <div style="font-size: 18px; color: #ffffff; font-weight: 700; margin-bottom: 10px;">{{ $record->name }}</div>
        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Teléfono</div>
                <div style="color: #ffffff;">{{ $record->phone ?: '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Email</div>
                <div style="color: #ffffff;">{{ $record->email ?: '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Estado comercial</div>
                <div style="color: #ffffff;">{{ $record->leadStatus?->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Agente</div>
                <div style="color: #ffffff;">{{ $record->user?->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Zona</div>
                <div style="color: #ffffff;">{{ $record->zone ?: '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Tipo de propiedad</div>
                <div style="color: #ffffff;">{{ $record->property_type ?: '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 15px; color: #ffffff; font-weight: 700; margin-bottom: 16px;">Notas y actividad</div>
        <div style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">
            Los mensajes de WhatsApp se ven en "Conversación".
        </div>

        @forelse ($timeline as $item)
            @php
                $isNote = $item['type'] === 'note';

                $dotColor = $isNote ? '#60a5fa' : '#9ca3af';
                $label    = $isNote ? 'Nota' : 'Actividad';

                $date = $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') : '-';
            @endphp

            <div style="display: flex; gap: 14px; padding: 10px 0; border-bottom: 1px solid #1f2937;">
                {{-- Dot --}}
                <div style="flex-shrink: 0; display: flex; flex-direction: column; align-items: center; padding-top: 4px;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: {{ $dotColor }};"></div>
                </div>

                {{-- Content --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <span style="font-size: 11px; font-weight: 700; color: {{ $dotColor }}; text-transform: uppercase; letter-spacing: .05em;">
                            {{ $label }}
                        </span>
                        <span style="font-size: 11px; color: #6b7280;">{{ $date }}</span>
                        @if ($item['actor'] ?? null)
                            <span style="font-size: 11px; color: #6b7280;">· {{ $item['actor'] }}</span>
                        @endif
                    </div>
                    <div style="color: #d1d5db; font-size: 13px; line-height: 1.6; white-space: pre-line; word-break: break-word;">
                        {{ $item['text'] }}
                    </div>
                </div>
            </div>
        @empty
            <div style="color: #9ca3af;">Todavía no hay actividad registrada para este lead.</div>
        @endforelse
    </div>

</div>
