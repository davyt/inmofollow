<?php

namespace App\Filament\Pages;

use App\Models\LeadStatus;
use App\Models\MessageTemplate;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class Flows extends Page
{
    protected static ?string                 $navigationLabel = 'Flows';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string                 $title           = 'Flujos de automatización';
    protected static ?int                    $navigationSort  = 10;
    protected static \UnitEnum|string|null   $navigationGroup = 'Automatizaciones';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }
    protected string                         $view            = 'filament.pages.flows';

    public array   $sequences  = [];
    public array   $statuses   = [];
    public array   $templates  = [];
    public array   $agents     = [];
    public ?int    $selectedId = null;
    public ?array  $flow       = null;

    // New sequence form
    public bool   $showNewForm      = false;
    public string $newName          = '';
    public string $newTriggerType   = 'status_change';
    public ?int   $newStatusId      = null;
    public string $newTriggerSource = '';

    // Step form
    public bool   $showStepForm        = false;
    public ?int   $editStepId          = null;
    public string $stepType            = 'send_template';
    public int    $stepDayOffset       = 0;
    public string $stepChannel         = 'whatsapp';
    public ?int   $stepTemplateId      = null;
    public string $stepMessage         = '';
    public ?int   $stepTargetStatusId  = null;
    public ?int   $stepTargetAgentId   = null;

    public function mount(): void
    {
        $this->loadMeta();
        $this->loadSequences();
    }

    private function loadMeta(): void
    {
        $user = Auth::user();

        $this->statuses = LeadStatus::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color'])
            ->toArray();

        $templateQuery = MessageTemplate::where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name');

        if ($user->isAgent()) {
            $templateQuery->where(fn ($q) => $q->where('scope', 'global')->orWhere('user_id', $user->id));
        }

        $this->templates = $templateQuery->get(['id', 'name'])->toArray();

        $this->agents = User::where('company_id', $user->company_id)
            ->where('active', true)
            ->whereIn('role', ['agent', 'supervisor'])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function loadSequences(): void
    {
        $user  = Auth::user();
        $query = Sequence::with(['leadStatus', 'steps'])
            ->where('company_id', $user->company_id)
            ->orderBy('name');

        if ($user->isAgent()) {
            $query->where(fn ($q) => $q
                ->where('scope', 'global')
                ->orWhere(fn ($q2) => $q2->where('scope', 'personal')->where('user_id', $user->id))
            );
        }

        $this->sequences = $query->get()->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'active'       => $s->active,
            'trigger_type' => $s->trigger_type ?? 'status_change',
            'trigger_name' => $s->leadStatus?->name,
            'steps_count'  => $s->steps->count(),
        ])->toArray();
    }

    public function selectSequence(int $id): void
    {
        $this->selectedId   = $id;
        $this->showNewForm  = false;
        $this->showStepForm = false;
        $this->loadFlow();
    }

    private function loadFlow(): void
    {
        if (! $this->selectedId) {
            $this->flow = null;
            return;
        }

        $seq = Sequence::with([
            'leadStatus',
            'steps' => fn ($q) => $q->orderBy('sort_order')->orderBy('day_offset'),
            'steps.messageTemplate',
        ])->find($this->selectedId);

        if (! $seq) {
            $this->flow = null;
            return;
        }

        $this->flow = [
            'id'           => $seq->id,
            'name'         => $seq->name,
            'active'       => $seq->active,
            'trigger_type' => $seq->trigger_type ?? 'status_change',
            'trigger_name' => $seq->leadStatus?->name,
            'steps'        => $seq->steps->map(fn ($step) => [
                'id'          => $step->id,
                'day_offset'  => $step->day_offset,
                'step_type'   => $step->step_type ?? 'send_template',
                'step_data'   => $step->step_data ?? [],
                'channel'     => $step->channel,
                'template_id' => $step->message_template_id,
                'label'       => $this->stepLabel($step),
                'active'      => $step->active,
            ])->values()->toArray(),
        ];
    }

    private function stepLabel($step): string
    {
        $type = $step->step_type ?? 'send_template';
        $data = $step->step_data ?? [];

        return match ($type) {
            'send_template' => $step->messageTemplate?->name ?? 'Sin plantilla',
            'send_message'  => 'Mensaje: ' . \Illuminate\Support\Str::limit($data['message'] ?? '', 40),
            'update_status' => 'Cambiar estado → ' . (LeadStatus::find($data['status_id'] ?? 0)?->name ?? '?'),
            'assign_agent'  => 'Asignar → ' . (User::find($data['agent_id'] ?? 0)?->name ?? '?'),
            'send_report'   => 'Ficha al agente' . (isset($data['agent_id']) ? ' (reasignar → ' . (User::find($data['agent_id'])?->name ?? '?') . ')' : ''),
            default         => $type,
        };
    }

    // --- New sequence ---

    public function openNewForm(): void
    {
        $this->showNewForm     = true;
        $this->selectedId      = null;
        $this->flow            = null;
        $this->newName          = '';
        $this->newTriggerType   = 'status_change';
        $this->newStatusId      = null;
        $this->newTriggerSource = '';
    }

    public function createSequence(): void
    {
        $user = Auth::user();
        $seq  = Sequence::create([
            'company_id'     => $user->company_id,
            'user_id'        => $user->id,
            'scope'          => $user->isAgent() ? 'personal' : 'global',
            'name'           => $this->newName ?: 'Flow sin nombre',
            'trigger_type'   => $this->newTriggerType,
            'lead_status_id' => $this->newTriggerType === 'status_change' ? $this->newStatusId : null,
            'trigger_source' => $this->newTriggerSource ?: null,
            'active'         => true,
        ]);

        $this->showNewForm = false;
        $this->loadSequences();
        $this->selectSequence($seq->id);
    }

    public function deleteSequence(int $id): void
    {
        Sequence::find($id)?->delete();
        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->flow       = null;
        }
        $this->loadSequences();
    }

    public function toggleSequence(int $id): void
    {
        $seq = Sequence::find($id);
        if ($seq) {
            $seq->update(['active' => ! $seq->active]);
            $this->loadSequences();
            if ($this->selectedId === $id) $this->loadFlow();
        }
    }

    // --- Step editing ---

    public function openNewStep(): void
    {
        $this->editStepId         = null;
        $this->stepType           = 'send_template';
        $this->stepDayOffset      = 0;
        $this->stepChannel        = 'whatsapp';
        $this->stepTemplateId     = null;
        $this->stepMessage        = '';
        $this->stepTargetStatusId = null;
        $this->stepTargetAgentId  = null;
        $this->showStepForm       = true;
    }

    public function openEditStep(int $stepId): void
    {
        $step = SequenceStep::find($stepId);
        if (! $step) return;

        $data = $step->step_data ?? [];

        $this->editStepId         = $stepId;
        $this->stepType           = $step->step_type ?? 'send_template';
        $this->stepDayOffset      = $step->day_offset;
        $this->stepChannel        = $step->channel ?? 'whatsapp';
        $this->stepTemplateId     = $step->message_template_id;
        $this->stepMessage        = $data['message'] ?? '';
        $this->stepTargetStatusId = $data['status_id'] ?? null;
        $this->stepTargetAgentId  = $data['agent_id'] ?? null;
        $this->showStepForm       = true;
    }

    public function saveStep(): void
    {
        if (! $this->selectedId) return;

        $maxOrder = SequenceStep::where('sequence_id', $this->selectedId)->max('sort_order') ?? -1;

        $stepData = match ($this->stepType) {
            'send_message'  => ['message' => $this->stepMessage],
            'update_status' => ['status_id' => $this->stepTargetStatusId],
            'assign_agent'  => ['agent_id' => $this->stepTargetAgentId],
            'send_report'   => $this->stepTargetAgentId ? ['agent_id' => $this->stepTargetAgentId] : [],
            default         => null,
        };

        $attrs = [
            'step_type'           => $this->stepType,
            'step_data'           => $stepData,
            'day_offset'          => $this->stepDayOffset,
            'message_template_id' => in_array($this->stepType, ['send_template']) ? $this->stepTemplateId : null,
            'channel'             => match ($this->stepType) {
                'send_template', 'send_message' => $this->stepChannel,
                'send_report'                   => 'agent_report',
                default                         => 'action',
            },
        ];

        if ($this->editStepId) {
            SequenceStep::find($this->editStepId)?->update($attrs);
        } else {
            SequenceStep::create(array_merge($attrs, [
                'sequence_id' => $this->selectedId,
                'sort_order'  => $maxOrder + 1,
                'active'      => true,
            ]));
        }

        $this->showStepForm = false;
        $this->loadFlow();
        $this->loadSequences();
    }

    public function deleteStep(int $stepId): void
    {
        SequenceStep::find($stepId)?->delete();
        $this->loadFlow();
        $this->loadSequences();
    }

    public function cancelStepForm(): void
    {
        $this->showStepForm = false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
