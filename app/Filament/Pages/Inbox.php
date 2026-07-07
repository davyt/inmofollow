<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\WaInboundMessage;
use Carbon\Carbon;
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
    protected static ?int                    $navigationSort  = 1;
    protected static ?string                 $navigationGroup = 'Comunicación';
    protected string                         $view            = 'filament.pages.inbox';

    public array $conversations  = [];
    public ?int  $selectedLeadId = null;

    public function mount(): void
    {
        $this->loadInbox();
    }

    public function loadInbox(): void
    {
        $user    = Auth::user();
        $leadIds = Lead::where('company_id', $user->company_id)
            ->when($user->isAgent(), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $lastInbound = WaInboundMessage::whereIn('lead_id', $leadIds)
            ->select('lead_id', 'body as message', 'received_at as at')
            ->orderBy('received_at', 'desc')
            ->get()
            ->unique('lead_id')
            ->keyBy('lead_id');

        $lastOutbound = ScheduledMessage::whereIn('lead_id', $leadIds)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->select('lead_id', 'message_body as message', 'sent_at as at')
            ->orderBy('sent_at', 'desc')
            ->get()
            ->unique('lead_id')
            ->keyBy('lead_id');

        $activeLeadIds = $lastInbound->keys()->merge($lastOutbound->keys())->unique();

        $leads = Lead::with('leadStatus')
            ->whereIn('id', $activeLeadIds)
            ->get()
            ->keyBy('id');

        $this->conversations = $activeLeadIds
            ->map(function ($leadId) use ($leads, $lastInbound, $lastOutbound) {
                $lead = $leads[$leadId] ?? null;
                if (! $lead) return null;

                $in  = $lastInbound[$leadId]  ?? null;
                $out = $lastOutbound[$leadId] ?? null;

                if ($in && $out) {
                    $last      = Carbon::parse($in->at)->gt(Carbon::parse($out->at)) ? $in : $out;
                    $direction = Carbon::parse($in->at)->gt(Carbon::parse($out->at)) ? 'in' : 'out';
                } elseif ($in) {
                    $last      = $in;
                    $direction = 'in';
                } else {
                    $last      = $out;
                    $direction = 'out';
                }

                return [
                    'id'           => $lead->id,
                    'name'         => $lead->name ?? 'Sin nombre',
                    'phone'        => $lead->phone ?? '',
                    'status_name'  => $lead->leadStatus->name ?? null,
                    'status_color' => $lead->leadStatus->color ?? '#6b7280',
                    'last_message' => Str::limit($last->message ?? '', 58),
                    'last_at'      => $last->at,
                    'direction'    => $direction,
                    'unread'       => $direction === 'in',
                ];
            })
            ->filter()
            ->sortByDesc('last_at')
            ->values()
            ->toArray();
    }

    public function selectLead(int $leadId): void
    {
        $this->selectedLeadId = $leadId;
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
