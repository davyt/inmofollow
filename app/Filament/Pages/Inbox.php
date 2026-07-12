<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class Inbox extends Page
{
    protected static ?string                 $navigationLabel = 'Conversaciones';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-chat-bubble-left-right';
    protected static ?string                 $title           = 'Inbox';
    protected static ?int                    $navigationSort  = 10;
    protected static \UnitEnum|string|null         $navigationGroup = 'Comunicación';
    protected string                         $view            = 'filament.pages.inbox';

    public array $conversations  = [];
    public ?int  $selectedLeadId = null;

    public int  $listLimit = 50;
    public bool $hasMore   = false;

    public bool  $showNewConversation = false;
    public string $leadSearch         = '';
    public array  $leadSearchResults  = [];

    public function mount(): void
    {
        $this->loadInbox();
    }

    private function baseLeadQuery()
    {
        $user = Auth::user();

        return Lead::where('company_id', $user->company_id)
            ->when($user->isAgent(), fn ($q) => $q->where('user_id', $user->id));
    }

    public function loadInbox(): void
    {
        $query = $this->baseLeadQuery()->whereNotNull('last_message_at');

        $total = (clone $query)->count();

        $leads = $query->with('leadStatus')
            ->orderByDesc('last_message_at')
            ->limit($this->listLimit)
            ->get();

        $this->hasMore = $total > $this->listLimit;

        $this->conversations = $leads
            ->map(fn (Lead $lead) => [
                'id'           => $lead->id,
                'name'         => $lead->name ?? 'Sin nombre',
                'phone'        => $lead->phone ?? '',
                'status_name'  => $lead->leadStatus->name ?? null,
                'status_color' => $lead->leadStatus->color ?? '#6b7280',
                'last_message' => Str::limit($lead->last_message_preview ?? '', 58),
                'last_at'      => $lead->last_message_at,
                'direction'    => $lead->last_message_direction ?? 'out',
                'unread'       => $lead->last_message_direction === 'in',
            ])
            ->toArray();
    }

    public function loadMore(): void
    {
        $this->listLimit += 50;
        $this->loadInbox();
    }

    public function selectLead(int $leadId): void
    {
        $this->selectedLeadId = $leadId;
    }

    public function openNewConversation(): void
    {
        $this->showNewConversation = true;
        $this->leadSearch          = '';
        $this->leadSearchResults   = [];
    }

    public function closeNewConversation(): void
    {
        $this->showNewConversation = false;
    }

    public function updatedLeadSearch(): void
    {
        $term = trim($this->leadSearch);

        if (mb_strlen($term) < 2) {
            $this->leadSearchResults = [];
            return;
        }

        $this->leadSearchResults = $this->baseLeadQuery()
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function startConversation(int $leadId): void
    {
        $this->selectedLeadId      = $leadId;
        $this->showNewConversation = false;
    }

    #[Computed]
    public function selectedLead(): ?Lead
    {
        if (! $this->selectedLeadId) return null;
        return Lead::find($this->selectedLeadId);
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
