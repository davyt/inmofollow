<?php

namespace App\Filament\Pages;

use App\Models\Broadcast;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class Broadcasts extends Page
{
    protected static ?string                 $navigationLabel = 'Broadcasts';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string                 $title           = 'Broadcasts';
    protected static ?int                    $navigationSort  = 2;
    protected static \UnitEnum|string|null         $navigationGroup = 'Comunicación';
    protected string                         $view            = 'filament.pages.broadcasts';

    // Form fields
    public string  $broadcastName   = '';
    public ?int    $templateId      = null;
    public array   $filterStatusIds = [];

    // State
    public array  $templates       = [];
    public array  $statuses        = [];
    public array  $history         = [];
    public int    $previewCount    = 0;
    public bool   $showConfirm     = false;
    public string $successMessage  = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->templates = MessageTemplate::where('company_id', $user->company_id)
            ->where('active', true)
            ->whereNotNull('meta_template_name')
            ->orderBy('name')
            ->get(['id', 'name', 'meta_template_name', 'body'])
            ->toArray();

        $this->statuses = LeadStatus::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color'])
            ->toArray();

        $this->loadHistory();
    }

    public function loadHistory(): void
    {
        $user = Auth::user();

        $this->history = Broadcast::with(['user', 'messageTemplate'])
            ->where('company_id', $user->company_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function preview(): void
    {
        $this->previewCount = $this->buildLeadQuery()->count();
        $this->showConfirm  = true;
        $this->successMessage = '';
    }

    public function cancelConfirm(): void
    {
        $this->showConfirm = false;
    }

    public function send(): void
    {
        $user = Auth::user();

        $leads = $this->buildLeadQuery()->get();

        if ($leads->isEmpty()) {
            $this->showConfirm = false;
            return;
        }

        $broadcast = Broadcast::create([
            'company_id'          => $user->company_id,
            'user_id'             => $user->id,
            'name'                => $this->broadcastName ?: 'Broadcast ' . now()->format('d/m/Y H:i'),
            'message_template_id' => $this->templateId,
            'lead_filters'        => ['status_ids' => $this->filterStatusIds],
            'status'              => 'queued',
            'total_count'         => $leads->count(),
        ]);

        foreach ($leads as $lead) {
            ScheduledMessage::create([
                'broadcast_id'        => $broadcast->id,
                'lead_id'             => $lead->id,
                'company_id'          => $lead->company_id,
                'user_id'             => $lead->user_id,
                'message_template_id' => $this->templateId,
                'channel'             => 'whatsapp',
                'status'              => 'pending',
                'scheduled_for'       => now(),
            ]);
        }

        $this->broadcastName   = '';
        $this->templateId      = null;
        $this->filterStatusIds = [];
        $this->showConfirm     = false;
        $this->previewCount    = 0;
        $this->successMessage  = "✓ Broadcast creado: {$leads->count()} mensajes en cola.";

        $this->loadHistory();
    }

    private function buildLeadQuery()
    {
        $user  = Auth::user();
        $query = Lead::where('company_id', $user->company_id)
            ->where('do_not_contact', false)
            ->where('whatsapp_consent', true)
            ->whereNotNull('phone');

        if ($user->isAgent()) {
            $query->where('user_id', $user->id);
        }

        if (! empty($this->filterStatusIds)) {
            $query->whereIn('lead_status_id', $this->filterStatusIds);
        }

        return $query;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
