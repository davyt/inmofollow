<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Pipeline extends Page
{
    protected static ?string                 $navigationLabel = 'Pipeline';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-view-columns';
    protected static ?string                 $title           = 'Pipeline Comercial';
    protected static ?int                    $navigationSort  = 20;
    protected static \UnitEnum|string|null         $navigationGroup = 'Comercial';
    protected string                         $view            = 'filament.pages.pipeline';

    public array  $statuses         = [];
    public array  $leadsByStatus    = [];

    // Filtros del tablero
    public ?int   $filterUserId     = null;
    public string $filterZone       = '';
    public string $searchTerm       = '';
    public array  $agents           = [];
    public array  $zones            = [];

    // Nuevo estado inline
    public bool   $showNewStatus    = false;
    public string $newStatusName    = '';
    public string $newStatusColor   = '#6366f1';

    // Edición inline de estado
    public ?int   $editingStatusId  = null;
    public string $editStatusName   = '';
    public string $editStatusColor  = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isSupervisor()) {
            $this->agents = User::where('company_id', $user->company_id)
                ->where('active', true)
                ->whereIn('role', ['agent', 'supervisor'])
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }

        $this->zones = Lead::where('company_id', $user->company_id)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct()
            ->orderBy('zone')
            ->pluck('zone')
            ->toArray();

        $this->loadBoard();
    }

    public function loadBoard(): void
    {
        $user = Auth::user();

        $this->statuses = LeadStatus::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $query = Lead::with('user')
            ->where('company_id', $user->company_id)
            ->where('do_not_contact', false);

        if ($user->isAgent()) {
            $query->where('user_id', $user->id);
        } elseif ($this->filterUserId) {
            $query->where('user_id', $this->filterUserId);
        }

        if ($this->filterZone !== '') {
            $query->where('zone', $this->filterZone);
        }

        if (trim($this->searchTerm) !== '') {
            $term = trim($this->searchTerm);
            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"));
        }

        $leads = $query->orderByRaw('next_follow_up_at IS NULL, next_follow_up_at ASC')->get();

        $this->leadsByStatus = [];
        foreach ($this->statuses as $status) {
            $this->leadsByStatus[$status['id']] = $leads
                ->where('lead_status_id', $status['id'])
                ->values()
                ->toArray();
        }
    }

    public function updatedFilterUserId(): void
    {
        $this->loadBoard();
    }

    public function updatedFilterZone(): void
    {
        $this->loadBoard();
    }

    public function updatedSearchTerm(): void
    {
        $this->loadBoard();
    }

    public function clearFilters(): void
    {
        $this->filterUserId = null;
        $this->filterZone   = '';
        $this->searchTerm   = '';
        $this->loadBoard();
    }

    public function moveLead(int $leadId, int $newStatusId): void
    {
        $user = Auth::user();
        $lead = Lead::where('company_id', $user->company_id)->findOrFail($leadId);

        if ($user->isAgent() && $lead->user_id !== $user->id) {
            return;
        }

        $lead->update(['lead_status_id' => $newStatusId]);
        $this->loadBoard();
    }

    public function openEditStatus(int $id): void
    {
        if ($this->editingStatusId === $id) {
            $this->editingStatusId = null;
            return;
        }
        $status = LeadStatus::find($id);
        if (! $status) return;

        $this->editingStatusId = $id;
        $this->editStatusName  = $status->name;
        $this->editStatusColor = $status->color ?? '#6b7280';
    }

    public function saveStatus(): void
    {
        if (! $this->editingStatusId) return;
        $name = trim($this->editStatusName);
        if (! $name) return;

        LeadStatus::find($this->editingStatusId)?->update([
            'name'  => $name,
            'color' => $this->editStatusColor,
        ]);

        $this->editingStatusId = null;
        $this->loadBoard();
    }

    public function moveStatus(int $id, string $direction): void
    {
        $user     = Auth::user();
        $statuses = LeadStatus::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get();

        $index = $statuses->search(fn ($s) => $s->id === $id);
        if ($index === false) return;

        $swapIndex = $direction === 'left' ? $index - 1 : $index + 1;
        if ($swapIndex < 0 || $swapIndex >= $statuses->count()) return;

        $a = $statuses[$index];
        $b = $statuses[$swapIndex];

        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();

        $this->loadBoard();
    }

    public function deleteStatus(int $id): void
    {
        $user   = Auth::user();
        $status = LeadStatus::where('company_id', $user->company_id)->find($id);
        if (! $status) return;

        // Desasignar leads antes de eliminar
        Lead::where('lead_status_id', $id)->update(['lead_status_id' => null]);
        $status->delete();

        if ($this->editingStatusId === $id) {
            $this->editingStatusId = null;
        }

        $this->loadBoard();
    }

    public function createStatus(): void
    {
        $name = trim($this->newStatusName);
        if (! $name) return;

        $user     = Auth::user();
        $maxOrder = LeadStatus::where('company_id', $user->company_id)->max('sort_order') ?? -1;

        LeadStatus::create([
            'company_id' => $user->company_id,
            'name'       => $name,
            'color'      => $this->newStatusColor,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newStatusName  = '';
        $this->newStatusColor = '#6366f1';
        $this->showNewStatus  = false;
        $this->loadBoard();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
