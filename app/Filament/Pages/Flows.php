<?php

namespace App\Filament\Pages;

use App\Models\LeadStatus;
use App\Models\MessageTemplate;
use App\Models\Sequence;
use App\Models\SequenceStep;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class Flows extends Page
{
    protected static ?string                 $navigationLabel = 'Flows';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string                 $title           = 'Flujos de automatización';
    protected static ?int                    $navigationSort  = 1;
    protected static \UnitEnum|string|null         $navigationGroup = 'Automatización';
    protected string                         $view            = 'filament.pages.flows';

    public array   $sequences    = [];
    public array   $statuses     = [];
    public array   $templates    = [];
    public ?int    $selectedId   = null;
    public ?array  $flow         = null;

    // New sequence form
    public bool   $showNewForm   = false;
    public string $newName       = '';
    public ?int   $newStatusId   = null;

    // Edit step modal
    public bool   $showStepForm  = false;
    public ?int   $editStepId    = null;  // null = nuevo paso
    public int    $stepDayOffset = 0;
    public ?int   $stepTemplateId = null;
    public string $stepChannel   = 'whatsapp';

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
    }

    public function loadSequences(): void
    {
        $user  = Auth::user();
        $query = Sequence::with(['leadStatus', 'steps.messageTemplate'])
            ->where('company_id', $user->company_id)
            ->orderBy('name');

        if ($user->isAgent()) {
            $query->where(fn ($q) => $q->where('scope', 'global')->orWhere(fn ($q2) => $q2->where('scope', 'personal')->where('user_id', $user->id)));
        }

        $this->sequences = $query->get()->map(fn ($s) => [
            'id'            => $s->id,
            'name'          => $s->name,
            'active'        => $s->active,
            'trigger_name'  => $s->leadStatus?->name,
            'trigger_color' => $s->leadStatus?->color ?? '#6b7280',
            'steps_count'   => $s->steps->count(),
        ])->toArray();
    }

    public function selectSequence(int $id): void
    {
        $this->selectedId  = $id;
        $this->showNewForm = false;
        $this->showStepForm = false;
        $this->loadFlow();
    }

    private function loadFlow(): void
    {
        if (! $this->selectedId) {
            $this->flow = null;
            return;
        }

        $seq = Sequence::with(['leadStatus', 'steps' => fn ($q) => $q->orderBy('sort_order')->orderBy('day_offset'), 'steps.messageTemplate'])
            ->find($this->selectedId);

        if (! $seq) {
            $this->flow = null;
            return;
        }

        $this->flow = [
            'id'            => $seq->id,
            'name'          => $seq->name,
            'active'        => $seq->active,
            'trigger_name'  => $seq->leadStatus?->name,
            'trigger_color' => $seq->leadStatus?->color ?? '#6b7280',
            'steps'         => $seq->steps->map(fn ($step) => [
                'id'            => $step->id,
                'day_offset'    => $step->day_offset,
                'channel'       => $step->channel,
                'template_name' => $step->messageTemplate?->name ?? 'Sin plantilla',
                'template_id'   => $step->message_template_id,
                'active'        => $step->active,
            ])->values()->toArray(),
        ];
    }

    // --- New sequence ---

    public function openNewForm(): void
    {
        $this->showNewForm  = true;
        $this->selectedId   = null;
        $this->flow         = null;
        $this->newName      = '';
        $this->newStatusId  = null;
    }

    public function createSequence(): void
    {
        $user = Auth::user();
        $seq  = Sequence::create([
            'company_id'     => $user->company_id,
            'user_id'        => $user->id,
            'scope'          => $user->isAgent() ? 'personal' : 'global',
            'name'           => $this->newName ?: 'Flow sin nombre',
            'lead_status_id' => $this->newStatusId,
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
        $this->editStepId      = null;
        $this->stepDayOffset   = 0;
        $this->stepTemplateId  = null;
        $this->stepChannel     = 'whatsapp';
        $this->showStepForm    = true;
    }

    public function openEditStep(int $stepId): void
    {
        $step = SequenceStep::find($stepId);
        if (! $step) return;

        $this->editStepId      = $stepId;
        $this->stepDayOffset   = $step->day_offset;
        $this->stepTemplateId  = $step->message_template_id;
        $this->stepChannel     = $step->channel;
        $this->showStepForm    = true;
    }

    public function saveStep(): void
    {
        if (! $this->selectedId) return;

        $maxOrder = SequenceStep::where('sequence_id', $this->selectedId)->max('sort_order') ?? -1;

        if ($this->editStepId) {
            SequenceStep::find($this->editStepId)?->update([
                'day_offset'          => $this->stepDayOffset,
                'message_template_id' => $this->stepTemplateId,
                'channel'             => $this->stepChannel,
            ]);
        } else {
            SequenceStep::create([
                'sequence_id'         => $this->selectedId,
                'day_offset'          => $this->stepDayOffset,
                'message_template_id' => $this->stepTemplateId,
                'channel'             => $this->stepChannel,
                'sort_order'          => $maxOrder + 1,
                'active'              => true,
            ]);
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
