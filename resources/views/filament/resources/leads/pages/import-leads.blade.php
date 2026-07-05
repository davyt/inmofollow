<div>
    {{-- Indicador de pasos --}}
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:32px;">
        <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; background:{{ $step >= 1 ? '#6366f1' : '#374151' }}; color:#fff;">1</div>
        <span style="font-size:14px; color:{{ $step >= 1 ? '#e5e7eb' : '#6b7280' }};">Archivo</span>
        <div style="flex:1; height:1px; background:#374151;"></div>
        <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; background:{{ $step >= 2 ? '#6366f1' : '#374151' }}; color:#fff;">2</div>
        <span style="font-size:14px; color:{{ $step >= 2 ? '#e5e7eb' : '#6b7280' }};">Mapear columnas</span>
        <div style="flex:1; height:1px; background:#374151;"></div>
        <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; background:{{ $step >= 3 ? '#6366f1' : '#374151' }}; color:#fff;">3</div>
        <span style="font-size:14px; color:{{ $step >= 3 ? '#e5e7eb' : '#6b7280' }};">Resultado</span>
    </div>

    {{-- PASO 1: Subir archivo --}}
    @if ($step === 1)
    <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
        <h2 style="font-size:18px; font-weight:700; color:#fff; margin-bottom:20px;">Subir archivo CSV</h2>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-size:13px; font-weight:600; color:#d1d5db; margin-bottom:8px;">
                Seleccioná el archivo
            </label>
            <input
                type="file"
                wire:model="csvFile"
                accept=".csv,.txt,text/csv"
                style="display:block; width:100%; font-size:14px; color:#9ca3af; cursor:pointer;"
            >
            <div wire:loading wire:target="csvFile" style="margin-top:10px; font-size:13px; color:#a5b4fc;">
                Analizando archivo...
            </div>
        </div>

        <div style="border-radius:8px; background:#1f2937; padding:16px; font-size:13px; color:#9ca3af;">
            <p style="font-weight:600; color:#d1d5db; margin-bottom:8px;">Formato esperado:</p>
            <ul style="list-style:disc; padding-left:20px; line-height:2;">
                <li>Primera fila: nombres de columnas (ej: nombre, telefono, email)</li>
                <li>Separador: coma <code>,</code> o punto y coma <code>;</code> — se detecta automáticamente</li>
                <li>Solo el campo <strong style="color:#e5e7eb;">nombre</strong> es obligatorio</li>
                <li>Booleanos: usar <code>1</code> para verdadero, <code>0</code> para falso</li>
                <li><strong style="color:#e5e7eb;">Estado</strong>: debe coincidir exactamente con el nombre de un estado ya creado en Estados de lead. Si no coincide, el lead se importa sin estado</li>
                <li>Si el <strong style="color:#e5e7eb;">teléfono</strong> ya existe en el sistema (en cualquier formato: local, con +598, con espacios), esa fila se omite como duplicado — no se crea un lead repetido</li>
            </ul>
            <button wire:click="downloadTemplate" style="margin-top:12px; color:#818cf8; background:none; border:none; cursor:pointer; font-size:13px; padding:0;">
                Descargar plantilla de ejemplo →
            </button>
        </div>
    </div>
    @endif

    {{-- PASO 2: Mapear columnas --}}
    @if ($step === 2)
    <div style="display:flex; flex-direction:column; gap:24px;">

        {{-- Vista previa del CSV --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:16px;">
                Vista previa (primeras {{ count($preview) }} filas)
            </h2>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                            <th style="text-align:left; padding:8px 12px; color:#9ca3af; border-bottom:1px solid #374151; white-space:nowrap;">
                                {{ $header }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview as $row)
                        <tr>
                            @foreach ($headers as $header)
                            <td style="padding:8px 12px; color:#e5e7eb; border-bottom:1px solid #1f2937; white-space:nowrap; max-width:200px; overflow:hidden; text-overflow:ellipsis;">
                                {{ $row[$header] ?? '' }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Perfiles de importación --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:4px;">Perfil de importación</h2>
            <p style="font-size:13px; color:#9ca3af; margin-bottom:16px;">
                Si ya guardaste un mapeo para esta fuente (ej. "2clics - Negocios", "MercadoLibre"), elegilo para autocompletar las columnas de abajo.
            </p>

            <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Usar perfil guardado</label>
                    <select
                        wire:model.live="selectedProfileId"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:220px;"
                    >
                        <option value="">— Mapeo manual —</option>
                        @foreach ($this->availableProfiles() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Origen fijo (opcional)</label>
                    <input
                        type="text"
                        wire:model="defaultSource"
                        placeholder="Ej: Mercado Libre"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:200px;"
                    >
                </div>
            </div>

            <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
                Si completás "Origen fijo", ese valor se usa para <strong style="color:#9ca3af;">todas</strong> las filas, sin importar qué columna tengas mapeada a Origen — útil cuando el archivo no trae esa información (como el scraping de MercadoLibre).
            </div>

            <div style="display:flex; gap:10px; align-items:center; border-top:1px solid #374151; padding-top:16px;">
                <input
                    type="text"
                    wire:model="newProfileName"
                    placeholder="Nombre del perfil (ej: 2clics - Negocios)"
                    style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; flex:1; max-width:280px;"
                >
                <button
                    wire:click="saveProfile"
                    style="padding:8px 16px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:13px; white-space:nowrap;"
                >
                    Guardar mapeo actual como perfil
                </button>
            </div>
        </div>

        {{-- Mapeo de columnas --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:4px;">Mapear columnas</h2>
            <p style="font-size:13px; color:#9ca3af; margin-bottom:16px;">
                Asociá cada campo del sistema con la columna correspondiente de tu CSV. Las columnas ya detectadas automáticamente aparecen pre-seleccionadas.
            </p>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151; width:40%;">Campo del sistema</th>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151;">Columna del CSV</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Filament\Resources\Leads\Pages\ImportLeads::getLeadFields() as $field => $label)
                    <tr>
                        <td style="padding:10px 12px; color:#e5e7eb; border-bottom:1px solid #1f2937;">
                            {{ $label }}
                        </td>
                        <td style="padding:10px 12px; border-bottom:1px solid #1f2937;">
                            <select
                                wire:model="mapping.{{ $field }}"
                                style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; width:100%; max-width:280px;"
                            >
                                <option value="">— No importar —</option>
                                @foreach ($headers as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display:flex; gap:12px; margin-top:24px;">
                <button
                    wire:click="startOver"
                    style="padding:10px 20px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:14px;"
                >
                    ← Volver
                </button>
                <button
                    wire:click="import"
                    wire:loading.attr="disabled"
                    wire:target="import"
                    style="padding:10px 24px; border-radius:8px; background:#6366f1; color:#fff; border:none; cursor:pointer; font-size:14px; font-weight:600;"
                >
                    <span wire:loading.remove wire:target="import">Importar leads →</span>
                    <span wire:loading wire:target="import">Importando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- PASO 3: Resultado --}}
    @if ($step === 3)
    <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
        <h2 style="font-size:18px; font-weight:700; color:#fff; margin-bottom:20px;">Importación completada</h2>

        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#4ade80;">{{ $results['imported'] ?? 0 }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Importados</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#38bdf8;">{{ $results['duplicated'] ?? 0 }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Duplicados (ya existían)</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#facc15;">{{ $results['skipped'] ?? 0 }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Omitidos (sin nombre)</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#f87171;">{{ count($results['errors'] ?? []) }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Errores</div>
            </div>
        </div>

        @if (!empty($results['errors']))
        <div style="background:#1f2937; border-radius:8px; padding:16px; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:600; color:#f87171; margin-bottom:10px;">Detalle de errores:</p>
            <ul style="list-style:disc; padding-left:20px; font-size:13px; color:#9ca3af; line-height:1.8;">
                @foreach ($results['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (!empty($results['unmatchedStatuses']))
        <div style="background:#1f2937; border-radius:8px; padding:16px; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:600; color:#facc15; margin-bottom:10px;">
                Estados sin coincidencia (esos leads se importaron sin estado asignado):
            </p>
            <ul style="list-style:disc; padding-left:20px; font-size:13px; color:#9ca3af; line-height:1.8;">
                @foreach ($results['unmatchedStatuses'] as $status)
                <li>"{{ $status }}" — no coincide con ningún estado existente. Creálo en Estados de lead o corregí el CSV.</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div style="display:flex; gap:12px;">
            <button
                wire:click="startOver"
                style="padding:10px 20px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:14px;"
            >
                Importar otro archivo
            </button>
            <a
                href="{{ \App\Filament\Resources\Leads\LeadResource::getUrl('index') }}"
                style="padding:10px 24px; border-radius:8px; background:#6366f1; color:#fff; text-decoration:none; font-size:14px; font-weight:600;"
            >
                Ver leads →
            </a>
        </div>
    </div>
    @endif
</div>
