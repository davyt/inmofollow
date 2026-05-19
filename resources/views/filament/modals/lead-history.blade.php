<div style="display: flex; flex-direction: column; gap: 22px;">

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 13px; color: #9ca3af; margin-bottom: 8px;">Lead / Propietario</div>

        <div style="font-size: 18px; color: #ffffff; font-weight: 700; margin-bottom: 10px;">
            {{ $record->name }}
        </div>

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

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 15px; color: #ffffff; font-weight: 700; margin-bottom: 12px;">
            Notas
        </div>

        @forelse ($notes as $note)
            <div style="border-bottom: 1px solid #374151; padding: 12px 0;">
                <div style="font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                    {{ $note->created_at?->format('d/m/Y H:i') }} · {{ $note->user?->name ?? 'Sistema' }}
                </div>
                <div style="color: #ffffff; line-height: 1.55; white-space: pre-line;">
                    {{ $note->note }}
                </div>
            </div>
        @empty
            <div style="color: #9ca3af;">Este lead todavía no tiene notas.</div>
        @endforelse
    </div>

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 15px; color: #ffffff; font-weight: 700; margin-bottom: 12px;">
            Mensajes programados
        </div>

        @forelse ($messages as $message)
            <div style="border-bottom: 1px solid #374151; padding: 12px 0;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span style="border-radius: 999px; background: rgba(245, 158, 11, .15); color: #facc15; padding: 4px 10px; font-size: 12px; font-weight: 700;">
                        {{ $message->channel === 'whatsapp' ? 'WhatsApp' : 'Email' }}
                    </span>

                    <span style="border-radius: 999px; background: rgba(59, 130, 246, .15); color: #93c5fd; padding: 4px 10px; font-size: 12px; font-weight: 700;">
                        {{ match ($message->status) {
                            'pending' => 'Pendiente',
                            'sent' => 'Enviado',
                            'cancelled' => 'Cancelado',
                            'failed' => 'Fallido',
                            default => $message->status,
                        } }}
                    </span>

                    <span style="color: #9ca3af; font-size: 12px;">
                        Programado:
                        @if ($message->scheduled_for)
                            {{ \Illuminate\Support\Carbon::parse($message->scheduled_for)->format('d/m/Y H:i') }}
                        @else
                            Sin fecha
                        @endif
                    </span>
                </div>

                <div style="color: #ffffff; line-height: 1.55; white-space: pre-line;">
                    {{ $message->message_body }}
                </div>
            </div>
        @empty
            <div style="color: #9ca3af;">Este lead todavía no tiene mensajes programados.</div>
        @endforelse
    </div>

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.75); border-radius: 12px; padding: 16px;">
        <div style="font-size: 15px; color: #ffffff; font-weight: 700; margin-bottom: 12px;">
            Actividad registrada
        </div>

        @forelse ($activities as $activity)
            <div style="border-bottom: 1px solid #374151; padding: 10px 0;">
                <div style="font-size: 12px; color: #9ca3af; margin-bottom: 4px;">
                    {{ $activity->created_at?->format('d/m/Y H:i') }} · {{ $activity->user?->name ?? 'Sistema' }}
                </div>
                <div style="color: #ffffff;">
                    {{ $activity->description ?: $activity->event }}
                </div>
            </div>
        @empty
            <div style="color: #9ca3af;">Todavía no hay actividad registrada para este lead.</div>
        @endforelse
    </div>

</div>
