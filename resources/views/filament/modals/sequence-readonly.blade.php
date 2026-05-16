<div style="display: flex; flex-direction: column; gap: 18px;">

    @if (! $canEdit)
        <div style="border: 1px solid rgba(245, 158, 11, 0.45); background: rgba(245, 158, 11, 0.12); border-radius: 12px; padding: 14px 16px; color: #facc15; font-size: 14px; line-height: 1.5;">
            <strong>Solo lectura:</strong> esta es una secuencia global. Podés verla y usarla, pero no modificarla.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
        <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.7); border-radius: 12px; padding: 14px;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: 6px;">
                Tipo
            </div>
            <div style="font-size: 15px; color: #ffffff; font-weight: 600;">
                {{ $record->scope === 'global' ? 'Global' : 'Personal' }}
            </div>
        </div>

        <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.7); border-radius: 12px; padding: 14px;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: 6px;">
                Estado disparador
            </div>
            <div style="font-size: 15px; color: #ffffff; font-weight: 600;">
                {{ $record->leadStatus?->name ?? '-' }}
            </div>
        </div>
    </div>

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.7); border-radius: 12px; padding: 14px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: 6px;">
            Nombre de la secuencia
        </div>
        <div style="font-size: 15px; color: #ffffff; font-weight: 600;">
            {{ $record->name }}
        </div>
    </div>

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.7); border-radius: 12px; padding: 14px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: 6px;">
            Descripción
        </div>
        <div style="font-size: 15px; color: #ffffff; line-height: 1.65;">
            {{ $record->description ?: 'Sin descripción.' }}
        </div>
    </div>

    <div style="border: 1px solid #374151; background: rgba(17, 24, 39, 0.7); border-radius: 12px; padding: 14px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; font-weight: 700; margin-bottom: 8px;">
            Estado
        </div>

        @if ($record->active)
            <span style="display: inline-flex; border-radius: 999px; background: rgba(34, 197, 94, .15); color: #86efac; padding: 5px 12px; font-size: 12px; font-weight: 700;">
                Activa
            </span>
        @else
            <span style="display: inline-flex; border-radius: 999px; background: rgba(239, 68, 68, .15); color: #fca5a5; padding: 5px 12px; font-size: 12px; font-weight: 700;">
                Inactiva
            </span>
        @endif
    </div>
</div>