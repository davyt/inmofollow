<x-filament-panels::page>

<style>
.fl-wrap       { display: flex; height: calc(100vh - 200px); gap: 0; border-radius: 12px; overflow: hidden; border: 1px solid #2d2d42; }
.fl-sidebar    { width: 260px; flex-shrink: 0; display: flex; flex-direction: column; background: #1a1a2e; border-right: 1px solid #2d2d42; }
.fl-main       { flex: 1; display: flex; flex-direction: column; background: #13131f; overflow: hidden; min-width: 0; }
.fl-canvas     { flex: 1; overflow-y: auto; padding: 32px 40px; display: flex; flex-direction: column; align-items: center; }
.fl-btn        { padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; transition: opacity .15s; }
.fl-btn-amber  { background: #f59e0b; color: #0f0f1a; }
.fl-btn-ghost  { background: #23233a; color: #94a3b8; border: 1px solid #2d2d42; }
.fl-btn-red    { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }
.fl-btn:hover  { opacity: .85; }
.fl-seq-item   { padding: 10px 14px; border-bottom: 1px solid #1e1e35; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background .1s; }
.fl-seq-item:hover  { background: #1e1e35; }
.fl-seq-item.active { background: #23233a; }

/* Flow nodes */
.fl-node       { width: 300px; border-radius: 10px; border: 1.5px solid #2d2d42; overflow: hidden; position: relative; }
.fl-node-trigger { border-color: #f59e0b88; background: #1a1a2e; }
.fl-node-step  { background: #1a1a2e; transition: border-color .15s; }
.fl-node-step:hover { border-color: #4b5563; }
.fl-connector  { width: 2px; height: 32px; background: #2d2d42; margin: 0 auto; position: relative; }
.fl-connector::after { content: ''; position: absolute; bottom: -1px; left: 50%; transform: translateX(-50%); border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid #2d2d42; }
.fl-add-btn    { width: 300px; border: 2px dashed #2d2d42; border-radius: 10px; padding: 14px; text-align: center; cursor: pointer; color: #374151; font-size: 13px; transition: all .15s; }
.fl-add-btn:hover { border-color: #f59e0b88; color: #f59e0b; }

/* Modal overlay */
.fl-modal-bg   { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 999; display: flex; align-items: center; justify-content: center; }
.fl-modal      { background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 12px; padding: 24px; width: 380px; max-width: 95vw; max-height: 90vh; overflow-y: auto; }
.fl-label      { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 5px; }
.fl-input      { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 7px; padding: 8px 11px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; }
.fl-input:focus { border-color: #f59e0b; }
.fl-select     { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 7px; padding: 8px 11px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; }
.fl-textarea   { width: 100%; background: #13131f; border: 1px solid #2d2d42; border-radius: 7px; padding: 8px 11px; color: #e2e8f0; font-size: 13px; outline: none; box-sizing: border-box; resize: vertical; min-height: 80px; }

/* Step type badges */
.badge-wa      { color: #25d366; }
.badge-email   { color: #60a5fa; }
.badge-status  { color: #f59e0b; }
.badge-agent   { color: #a78bfa; }
.badge-msg     { color: #34d399; }
.badge-report  { color: #fb923c; }
</style>

<div style="display:flex;justify-content:flex-end;margin-bottom:10px;gap:8px;">
    <a href="/davyt/message-templates/create"
       style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#23233a;border:1px solid #2d2d42;border-radius:7px;color:#94a3b8;font-size:12px;font-weight:600;text-decoration:none;"
       onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#2d2d42'">
        <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nueva plantilla
    </a>
    <a href="/davyt/message-templates"
       style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#23233a;border:1px solid #2d2d42;border-radius:7px;color:#94a3b8;font-size:12px;font-weight:600;text-decoration:none;"
       onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#2d2d42'">
        <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Ver plantillas
    </a>
</div>

<div class="fl-wrap">

    {{-- Sidebar: lista de flows --}}
    <div class="fl-sidebar">
        <div style="padding: 14px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 13px; font-weight: 600; color: #94a3b8;">{{ count($sequences) }} flows</span>
            <button class="fl-btn fl-btn-amber" wire:click="openNewForm" style="padding: 5px 10px; font-size: 11px;">+ Nuevo</button>
        </div>

        <div style="flex: 1; overflow-y: auto;">
            @forelse($sequences as $seq)
            <div
                class="fl-seq-item {{ $selectedId === $seq['id'] ? 'active' : '' }}"
                wire:click="selectSequence({{ $seq['id'] }})"
            >
                <div style="width: 8px; height: 8px; border-radius: 50%; background: {{ $seq['active'] ? '#4ade80' : '#374151' }}; flex-shrink: 0;"></div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 13px; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $seq['name'] }}</div>
                    <div style="font-size: 11px; color: #4b5563; margin-top: 1px;">
                        {{ $seq['steps_count'] }} paso{{ $seq['steps_count'] !== 1 ? 's' : '' }}
                        @if($seq['trigger_type'] === 'lead_created')
                        · Lead creado
                        @elseif($seq['trigger_name'])
                        · {{ $seq['trigger_name'] }}
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="padding: 32px 16px; text-align: center; color: #374151; font-size: 12px;">
                No hay flows.<br>Creá el primero.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Main: canvas del flow seleccionado --}}
    <div class="fl-main">

        {{-- Formulario nuevo flow --}}
        @if($showNewForm)
        <div style="padding: 24px 32px; border-bottom: 1px solid #2d2d42;">
            <div style="font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 16px;">Nuevo flow</div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                <div>
                    <label class="fl-label">Nombre</label>
                    <input class="fl-input" wire:model="newName" placeholder="Ej: Bienvenida" style="width: 200px;">
                </div>
                <div>
                    <label class="fl-label">Tipo de trigger</label>
                    <select class="fl-select" wire:model.live="newTriggerType" style="width: 200px;">
                        <option value="status_change">⚡ Cambio de estado</option>
                        <option value="lead_created">🆕 Lead creado</option>
                    </select>
                </div>
                @if($newTriggerType === 'status_change')
                <div>
                    <label class="fl-label">Estado que dispara</label>
                    <select class="fl-select" wire:model="newStatusId" style="width: 200px;">
                        <option value="">Sin filtro de estado</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="fl-label">Solo si Origen es (opcional)</label>
                    <select class="fl-select" wire:model="newTriggerSource" style="width: 180px;">
                        <option value="">Cualquier origen</option>
                        <option value="ml">MercadoLibre (ml)</option>
                        <option value="2clics">2clics</option>
                        <option value="web">Web</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                <button class="fl-btn fl-btn-amber" wire:click="createSequence">Crear</button>
                <button class="fl-btn fl-btn-ghost" wire:click="$set('showNewForm', false)">Cancelar</button>
            </div>
        </div>
        @endif

        @if($flow)

        {{-- Header del flow --}}
        <div style="padding: 14px 24px; border-bottom: 1px solid #2d2d42; display: flex; align-items: center; gap: 12px; background: #1a1a2e;">
            <div style="flex: 1;">
                <div style="font-size: 15px; font-weight: 700; color: #e2e8f0;">{{ $flow['name'] }}</div>
            </div>
            <button
                class="fl-btn {{ $flow['active'] ? 'fl-btn-ghost' : 'fl-btn-amber' }}"
                wire:click="toggleSequence({{ $flow['id'] }})"
                style="font-size: 11px;"
            >
                {{ $flow['active'] ? '⏸ Pausar' : '▶ Activar' }}
            </button>
            <button
                class="fl-btn fl-btn-red"
                wire:click="deleteSequence({{ $flow['id'] }})"
                wire:confirm="¿Eliminar este flow y todos sus pasos?"
                style="font-size: 11px;"
            >
                Eliminar
            </button>
        </div>

        {{-- Canvas del flow --}}
        <div class="fl-canvas">

            {{-- Nodo trigger --}}
            <div class="fl-node fl-node-trigger">
                <div style="height: 3px; background: #f59e0b;"></div>
                <div style="padding: 14px 16px;">
                    <div style="font-size: 10px; font-weight: 700; color: #f59e0b; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 6px;">⚡ Trigger</div>
                    @if($flow['trigger_type'] === 'lead_created')
                    <div style="font-size: 13px; color: #e2e8f0; font-weight: 600;">🆕 Lead creado</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Se dispara cuando se crea un lead nuevo</div>
                    @elseif($flow['trigger_name'])
                    <div style="font-size: 13px; color: #e2e8f0; font-weight: 600;">Lead cambia a "{{ $flow['trigger_name'] }}"</div>
                    @else
                    <div style="font-size: 13px; color: #4b5563; font-style: italic;">Sin trigger de estado (manual)</div>
                    @endif
                </div>
            </div>

            @foreach($flow['steps'] as $step)

            {{-- Conector --}}
            <div class="fl-connector"></div>

            {{-- Día badge si > 0 --}}
            @if($step['day_offset'] > 0)
            <div style="font-size: 11px; color: #64748b; background: #1a1a2e; border: 1px solid #2d2d42; border-radius: 999px; padding: 2px 10px; margin: -4px 0;">
                ⏱ Esperar {{ $step['day_offset'] }} día{{ $step['day_offset'] !== 1 ? 's' : '' }}
            </div>
            <div class="fl-connector"></div>
            @endif

            {{-- Nodo paso --}}
            @php
                $stepType = $step['step_type'];
                $accentColor = match($stepType) {
                    'send_template'          => $step['channel'] === 'whatsapp' ? '#25d366' : '#3b82f6',
                    'send_message'           => '#34d399',
                    'update_status'          => '#f59e0b',
                    'assign_agent'           => '#8b5cf6',
                    'send_report'            => '#fb923c',
                    'send_template_to_agent' => '#e879f9',
                    default                  => '#6b7280',
                };
                $badgeLabel = match($stepType) {
                    'send_template'          => $step['channel'] === 'whatsapp' ? '📱 Plantilla WhatsApp' : '✉️ Plantilla Email',
                    'send_message'           => '💬 Mensaje libre',
                    'update_status'          => '🔄 Cambiar estado',
                    'assign_agent'           => '👤 Asignar agente',
                    'send_report'            => '📋 Ficha al agente',
                    'send_template_to_agent' => '📨 Plantilla → Agente',
                    default                  => $stepType,
                };
            @endphp
            <div class="fl-node fl-node-step" style="{{ !$step['active'] ? 'opacity:.5;' : '' }}">
                <div style="height: 3px; background: {{ $accentColor }};"></div>
                <div style="padding: 12px 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 10px; font-weight: 700; color: {{ $accentColor }}; text-transform: uppercase; letter-spacing: .06em;">
                            {{ $badgeLabel }}
                        </span>
                        <span style="font-size: 11px; background: #23233a; color: #94a3b8; border-radius: 999px; padding: 2px 8px;">
                            Día {{ $step['day_offset'] }}
                        </span>
                    </div>
                    <div style="font-size: 13px; color: #e2e8f0; font-weight: 500; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $step['label'] }}
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button class="fl-btn fl-btn-ghost" wire:click="openEditStep({{ $step['id'] }})" style="font-size: 10px; padding: 4px 10px;">Editar</button>
                        <button class="fl-btn fl-btn-red" wire:click="deleteStep({{ $step['id'] }})" wire:confirm="¿Eliminar este paso?" style="font-size: 10px; padding: 4px 10px;">Eliminar</button>
                    </div>
                </div>
            </div>

            @endforeach

            {{-- Botón agregar paso --}}
            <div class="fl-connector" style="background: #1e1e35;"></div>
            <div class="fl-add-btn" wire:click="openNewStep">
                + Agregar paso
            </div>

        </div>

        @elseif(!$showNewForm)

        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #374151;">
            <div style="font-size: 40px; margin-bottom: 12px;">⚡</div>
            <div style="font-size: 14px; font-weight: 500; color: #4b5563;">Seleccioná un flow</div>
            <div style="font-size: 12px; color: #374151; margin-top: 4px;">o creá uno nuevo con el botón "Nuevo"</div>
        </div>

        @endif

    </div>

</div>

{{-- Modal de edición de paso --}}
@if($showStepForm)
<div class="fl-modal-bg" wire:click.self="cancelStepForm">
    <div class="fl-modal">
        <div style="font-size: 15px; font-weight: 700; color: #e2e8f0; margin-bottom: 18px;">
            {{ $editStepId ? 'Editar paso' : 'Nuevo paso' }}
        </div>

        {{-- Tipo de paso --}}
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Tipo de acción</label>
            <select class="fl-select" wire:model.live="stepType">
                <option value="send_template">📋 Enviar plantilla al lead</option>
                <option value="send_template_to_agent">📨 Enviar plantilla a agente</option>
                <option value="send_message">💬 Enviar mensaje libre</option>
                <option value="update_status">🔄 Cambiar estado del lead</option>
                <option value="assign_agent">👤 Asignar agente</option>
                <option value="send_report">📋 Enviar ficha al agente</option>
            </select>
        </div>

        {{-- Campos por tipo --}}
        @if($stepType === 'send_template')
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Canal</label>
            <select class="fl-select" wire:model="stepChannel">
                <option value="whatsapp">📱 WhatsApp</option>
                <option value="email">✉️ Email</option>
            </select>
        </div>
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Plantilla</label>
            <select class="fl-select" wire:model="stepTemplateId">
                <option value="">Sin plantilla</option>
                @foreach($templates as $t)
                <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>

        @elseif($stepType === 'send_message')
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Mensaje</label>
            <textarea class="fl-textarea" wire:model="stepMessage" placeholder="Escribí el mensaje... Podés usar @{{nombre}}, @{{zona}}, @{{agente}}"></textarea>
        </div>

        @elseif($stepType === 'update_status')
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Nuevo estado</label>
            <select class="fl-select" wire:model="stepTargetStatusId">
                <option value="">Seleccioná un estado</option>
                @foreach($statuses as $s)
                <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                @endforeach
            </select>
        </div>

        @elseif($stepType === 'assign_agent')
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Agente</label>
            <select class="fl-select" wire:model="stepTargetAgentId">
                <option value="">Seleccioná un agente</option>
                @foreach($agents as $a)
                <option value="{{ $a['id'] }}">{{ $a['name'] }}</option>
                @endforeach
            </select>
        </div>

        @elseif($stepType === 'send_template_to_agent')
        <div style="background: #0f1117; border: 1px solid #2d2d42; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 12px; color: #64748b; line-height: 1.6;">
            La plantilla se envía al WhatsApp del agente seleccionado, con las variables del lead sustituidas. No le llega nada al lead.
        </div>
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Agente destinatario</label>
            <select class="fl-select" wire:model="stepTargetAgentId">
                <option value="">Seleccioná un agente</option>
                @foreach($agents as $a)
                <option value="{{ $a['id'] }}">{{ $a['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Plantilla a enviar</label>
            <select class="fl-select" wire:model="stepTemplateId">
                <option value="">Seleccioná una plantilla</option>
                @foreach($templates as $t)
                <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>

        @elseif($stepType === 'send_report')
        <div style="background: #0f1117; border: 1px solid #2d2d42; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 12px; color: #64748b; line-height: 1.6;">
            Se genera y envía por WhatsApp al agente asignado una ficha completa del lead:<br>
            nombre, teléfono, email, propiedad (zona, precio, dorm, m², link ML), notas, estado y origen.
        </div>
        <div style="margin-bottom: 14px;">
            <label class="fl-label">Reasignar a agente (opcional)</label>
            <select class="fl-select" wire:model="stepTargetAgentId">
                <option value="">Mantener agente actual</option>
                @foreach($agents as $a)
                <option value="{{ $a['id'] }}">{{ $a['name'] }}</option>
                @endforeach
            </select>
            <div style="font-size: 11px; color: #4b5563; margin-top: 4px;">Si elegís un agente, se reasigna el lead antes de enviar la ficha.</div>
        </div>
        @endif

        {{-- Días --}}
        <div style="margin-bottom: 20px;">
            <label class="fl-label">Días desde el inicio del flow</label>
            <input type="number" class="fl-input" wire:model="stepDayOffset" min="0" style="width: 100px;">
            <div style="font-size: 11px; color: #4b5563; margin-top: 4px;">
                0 = mismo día del trigger · 1 = al día siguiente
                @if(in_array($stepType, ['update_status', 'assign_agent']))
                <span style="color: #f59e0b;"> · Con 0 se ejecuta de inmediato al crear el flow.</span>
                @endif
                @if($stepType === 'send_report')
                <span style="color: #fb923c;"> · Con 0 se envía al agente el mismo día del trigger.</span>
                @endif
            </div>
        </div>

        <div style="display: flex; gap: 8px;">
            <button class="fl-btn fl-btn-amber" wire:click="saveStep">Guardar</button>
            <button class="fl-btn fl-btn-ghost" wire:click="cancelStepForm">Cancelar</button>
        </div>
    </div>
</div>
@endif

</x-filament-panels::page>
