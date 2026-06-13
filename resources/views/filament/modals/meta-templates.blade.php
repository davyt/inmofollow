<div style="display: flex; flex-direction: column; gap: 16px;">

    @if ($error)
        <div style="background: rgba(239,68,68,.1); border: 1px solid #ef4444; border-radius: 8px; padding: 14px; color: #fca5a5;">
            {{ $error }}
        </div>
    @elseif (empty($templates))
        <div style="color: #9ca3af; text-align: center; padding: 24px;">
            No se encontraron plantillas aprobadas en esta cuenta.
        </div>
    @else
        <div style="font-size: 13px; color: #9ca3af; margin-bottom: 4px;">
            {{ count($templates) }} plantilla(s) encontrada(s). Copiá el nombre exacto al campo "Nombre de la plantilla en Meta" dentro de tu plantilla InmoFollow.
        </div>

        @foreach ($templates as $tpl)
            @php
                $status = $tpl['status'] ?? 'UNKNOWN';
                $statusColor = match($status) {
                    'APPROVED' => '#34d399',
                    'PENDING'  => '#facc15',
                    'REJECTED' => '#f87171',
                    default    => '#9ca3af',
                };
                $statusLabel = match($status) {
                    'APPROVED' => 'Aprobada',
                    'PENDING'  => 'Pendiente de aprobación',
                    'REJECTED' => 'Rechazada',
                    default    => $status,
                };

                $bodyText = null;
                foreach (($tpl['components'] ?? []) as $component) {
                    if (($component['type'] ?? '') === 'BODY') {
                        $bodyText = $component['text'] ?? null;
                        break;
                    }
                }
            @endphp

            <div style="border: 1px solid #374151; border-radius: 10px; padding: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                    <code style="font-size: 14px; font-weight: 700; color: #60a5fa; background: rgba(96,165,250,.1); padding: 3px 8px; border-radius: 4px;">
                        {{ $tpl['name'] ?? '-' }}
                    </code>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <span style="font-size: 11px; color: #9ca3af;">{{ $tpl['language'] ?? '-' }}</span>
                        <span style="font-size: 11px; font-weight: 700; color: {{ $statusColor }}; background: rgba(0,0,0,.2); padding: 2px 8px; border-radius: 999px;">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                @if ($bodyText)
                    <div style="font-size: 13px; color: #d1d5db; white-space: pre-line; line-height: 1.6; background: rgba(0,0,0,.2); padding: 10px; border-radius: 6px;">
                        {{ $bodyText }}
                    </div>
                @endif
            </div>
        @endforeach
    @endif

</div>
