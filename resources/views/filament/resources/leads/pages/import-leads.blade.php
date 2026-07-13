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
        <h2 style="font-size:18px; font-weight:700; color:#fff; margin-bottom:20px;">Subir archivo CSV o Excel</h2>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-size:13px; font-weight:600; color:#d1d5db; margin-bottom:8px;">Seleccioná el archivo</label>
            <input
                type="file"
                wire:model="csvFile"
                accept=".csv,.txt,text/csv,.xlsx,.xlsm,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                style="display:block; width:100%; font-size:14px; color:#9ca3af; cursor:pointer;"
            >
            <div wire:loading wire:target="csvFile" style="margin-top:10px; font-size:13px; color:#a5b4fc;">
                Analizando archivo...
            </div>
        </div>

        <div style="border-radius:8px; background:#1f2937; padding:16px; font-size:13px; color:#9ca3af;">
            <p style="font-weight:600; color:#d1d5db; margin-bottom:8px;">Fuentes compatibles:</p>
            <ul style="list-style:disc; padding-left:20px; line-height:2;">
                <li><strong style="color:#e5e7eb;">CSV de MercadoLibre</strong> — usá el botón "Preset ML" en el paso 2 para autoasignar todas las columnas</li>
                <li><strong style="color:#e5e7eb;">Export de 2clics</strong> — se detecta la fila de título automáticamente</li>
                <li><strong style="color:#e5e7eb;">Cualquier CSV/XLSX</strong> — mapeás las columnas manualmente</li>
                <li>Si el teléfono ya existe, el lead no se duplica — se agrega la propiedad al lead existente</li>
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

        {{-- Vista previa --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:16px;">
                Vista previa (primeras {{ count($preview) }} filas)
            </h2>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                            <th style="text-align:left; padding:8px 12px; color:#9ca3af; border-bottom:1px solid #374151; white-space:nowrap;">{{ $header }}</th>
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

        {{-- Perfiles + preset ML --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                <div>
                    <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:2px;">Perfil de importación</h2>
                    <p style="font-size:13px; color:#9ca3af;">Elegí un perfil guardado o aplicá el preset de MercadoLibre.</p>
                </div>
                <button
                    wire:click="applyMlPreset"
                    style="padding:9px 18px; border-radius:8px; background:#fbbf24; color:#0f0f1a; border:none; cursor:pointer; font-size:13px; font-weight:700; white-space:nowrap;"
                >
                    ⚡ Preset MercadoLibre
                </button>
            </div>

            <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Usar perfil guardado</label>
                    <select wire:model.live="selectedProfileId" style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:220px;">
                        <option value="">— Mapeo manual —</option>
                        @foreach ($this->availableProfiles() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Origen fijo (opcional)</label>
                    <input type="text" wire:model="defaultSource" placeholder="Ej: ml, 2clics, web"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:160px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Acepta WhatsApp (fijo)</label>
                    <select wire:model="defaultWhatsappConsent"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:160px;">
                        <option value="">— No fijar (usar columna) —</option>
                        <option value="1">Sí, para todos</option>
                        <option value="0">No, para todos</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Acepta Email (fijo)</label>
                    <select wire:model="defaultEmailConsent"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:160px;">
                        <option value="">— No fijar (usar columna) —</option>
                        <option value="1">Sí, para todos</option>
                        <option value="0">No, para todos</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px;">Estado fijo (opcional)</label>
                    <select wire:model="defaultLeadStatusId"
                        style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; min-width:160px;">
                        <option value="">— No fijar (usar columna) —</option>
                        @foreach($this->availableStatuses() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
                "Origen fijo" y los "fijo" de WhatsApp/Email/Estado se aplican a todas las filas, sin importar si mapeaste una columna para eso. Para flows por origen usá valores cortos como <code style="background:#1f2937;padding:1px 4px;border-radius:3px;">ml</code>, <code style="background:#1f2937;padding:1px 4px;border-radius:3px;">2clics</code>.
            </div>

            <div style="display:flex; gap:10px; align-items:center; border-top:1px solid #374151; padding-top:16px;">
                <input type="text" wire:model="newProfileName" placeholder="Nombre del perfil (ej: MercadoLibre Canelones)"
                    style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; flex:1; max-width:280px;">
                <button wire:click="saveProfile" style="padding:8px 16px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:13px; white-space:nowrap;">
                    Guardar mapeo como perfil
                </button>
            </div>
        </div>

        {{-- Mapeo de contacto --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:4px;">📋 Datos del contacto</h2>
            <p style="font-size:13px; color:#9ca3af; margin-bottom:16px;">
                Columnas del CSV que se guardan en el perfil del lead. Para imports de MercadoLibre solo es necesario mapear el Teléfono.
            </p>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151; width:40%;">Campo</th>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151;">Columna del CSV</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Filament\Resources\Leads\Pages\ImportLeads::getLeadFields() as $field => $label)
                    <tr>
                        <td style="padding:10px 12px; color:#e5e7eb; border-bottom:1px solid #1f2937;">{{ $label }}</td>
                        <td style="padding:10px 12px; border-bottom:1px solid #1f2937;">
                            <select wire:model="mapping.{{ $field }}"
                                style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; width:100%; max-width:280px;">
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
        </div>

        {{-- Mapeo de propiedad --}}
        <div style="border:1px solid #374151; background:rgba(17,24,39,0.75); border-radius:12px; padding:24px;">
            <h2 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:4px;">🏠 Datos de la propiedad <span style="font-size:12px; font-weight:400; color:#6b7280;">(opcional)</span></h2>
            <p style="font-size:13px; color:#9ca3af; margin-bottom:16px;">
                Se guarda en el expediente de propiedad del lead. La IA usa estos datos para dar contexto en las conversaciones.
                Si dejás todo en blanco, no se crea expediente.
            </p>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151; width:40%;">Campo</th>
                        <th style="text-align:left; padding:10px 12px; color:#9ca3af; border-bottom:1px solid #374151;">Columna del CSV</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Filament\Resources\Leads\Pages\ImportLeads::getListingFields() as $field => $label)
                    <tr>
                        <td style="padding:10px 12px; color:#e5e7eb; border-bottom:1px solid #1f2937;">{{ $label }}</td>
                        <td style="padding:10px 12px; border-bottom:1px solid #1f2937;">
                            <select wire:model="listingMapping.{{ $field }}"
                                style="background:#1f2937; color:#e5e7eb; border:1px solid #374151; border-radius:6px; padding:6px 10px; font-size:13px; width:100%; max-width:280px;">
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
        </div>

        {{-- Botones --}}
        <div style="display:flex; gap:12px;">
            <button wire:click="startOver" style="padding:10px 20px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:14px;">
                ← Volver
            </button>
            <button wire:click="import" wire:loading.attr="disabled" wire:target="import"
                style="padding:10px 24px; border-radius:8px; background:#6366f1; color:#fff; border:none; cursor:pointer; font-size:14px; font-weight:600;">
                <span wire:loading.remove wire:target="import">Importar →</span>
                <span wire:loading wire:target="import">Importando...</span>
            </button>
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
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Leads nuevos</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#38bdf8;">{{ $results['duplicated'] ?? 0 }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Ya existían (propiedad agregada)</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#facc15;">{{ $results['skipped'] ?? 0 }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Omitidos</div>
            </div>
            <div style="background:#1f2937; border-radius:10px; padding:16px; text-align:center;">
                <div style="font-size:32px; font-weight:800; color:#f87171;">{{ count($results['errors'] ?? []) }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:4px;">Errores</div>
            </div>
        </div>

        @if (!empty($results['errors']))
        <div style="background:#1f2937; border-radius:8px; padding:16px; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:600; color:#f87171; margin-bottom:10px;">Errores:</p>
            <ul style="list-style:disc; padding-left:20px; font-size:13px; color:#9ca3af; line-height:1.8;">
                @foreach ($results['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (!empty($results['unmatchedStatuses']))
        <div style="background:#1f2937; border-radius:8px; padding:16px; margin-bottom:20px;">
            <p style="font-size:13px; font-weight:600; color:#facc15; margin-bottom:10px;">Estados sin coincidencia (importados sin estado):</p>
            <ul style="list-style:disc; padding-left:20px; font-size:13px; color:#9ca3af; line-height:1.8;">
                @foreach ($results['unmatchedStatuses'] as $status)
                <li>"{{ $status }}"</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div style="display:flex; gap:12px;">
            <button wire:click="startOver" style="padding:10px 20px; border-radius:8px; background:#374151; color:#e5e7eb; border:none; cursor:pointer; font-size:14px;">
                Importar otro archivo
            </button>
            <a href="{{ \App\Filament\Resources\Leads\LeadResource::getUrl('index') }}"
                style="padding:10px 24px; border-radius:8px; background:#6366f1; color:#fff; text-decoration:none; font-size:14px; font-weight:600;">
                Ver leads →
            </a>
        </div>
    </div>
    @endif
</div>
