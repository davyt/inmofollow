<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PipelineBoard extends Component
{
    public array $statuses      = [];
    public array $leadsByStatus = [];

    public function mount(): void
    {
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

    public function render()
    {
        return view('livewire.pipeline-board');
    }
}
