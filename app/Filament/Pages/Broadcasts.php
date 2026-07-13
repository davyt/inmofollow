<?php

namespace App\Filament\Pages;

use App\Models\Broadcast;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class Broadcasts extends Page
{
    private const TIMEZONE = 'America/Montevideo';

    protected static ?string                 $navigationLabel = 'Broadcast';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string                 $title           = 'Broadcast';
    protected static ?int                    $navigationSort  = 20;
    protected static \UnitEnum|string|null         $navigationGroup = 'Comunicación';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }
    protected string                         $view            = 'filament.pages.broadcasts';

    // Form fields
    public string  $broadcastName   = '';
    public ?int    $templateId      = null;
    public array   $filterStatusIds = [];
    public array   $filterSources   = [];
    public int     $leadLimit       = 0;
    public string  $startDate       = '';
    public string  $startTime       = '';
    public bool    $batchEnabled    = false;
    public int     $batchSize       = 100;

    // State
    public array  $templates       = [];
    public array  $statuses        = [];
    public array  $sources         = [];
    public array  $history         = [];
    public int    $previewCount    = 0;
    public array  $previewSchedule = [];
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

        $this->sources = Lead::where('company_id', $user->company_id)
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->toArray();

        $this->startDate = now(self::TIMEZONE)->format('Y-m-d');
        $this->startTime = now(self::TIMEZONE)->format('H:i');

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
        $total = $this->buildLeadQuery()->count();
        $this->previewCount    = $this->leadLimit > 0 ? min($total, $this->leadLimit) : $total;
        $this->previewSchedule = $this->buildSchedule($this->previewCount);
        $this->showConfirm     = true;
        $this->successMessage  = '';
    }

    /**
     * Devuelve [['date' => 'd/m/Y H:i', 'count' => n], ...]. Sin tandas es un solo bloque
     * en la fecha/hora elegida; con tandas, un bloque por día empezando ahí.
     */
    private function buildSchedule(int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $startAt   = $this->resolveStartAt();
        $batchSize = ($this->batchEnabled && $this->batchSize > 0) ? $this->batchSize : $count;

        $schedule  = [];
        $remaining = $count;
        $day       = 0;

        while ($remaining > 0) {
            $batch       = min($batchSize, $remaining);
            $schedule[]  = ['date' => $startAt->copy()->addDays($day)->format('d/m/Y H:i'), 'count' => $batch];
            $remaining  -= $batch;
            $day++;
        }

        return $schedule;
    }

    private function resolveStartAt(): Carbon
    {
        $date = $this->startDate ?: now(self::TIMEZONE)->format('Y-m-d');
        $time = $this->startTime ?: now(self::TIMEZONE)->format('H:i');

        return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", self::TIMEZONE);
    }

    public function cancelConfirm(): void
    {
        $this->showConfirm = false;
    }

    public function send(): void
    {
        $user = Auth::user();

        $query = $this->buildLeadQuery();
        $leads = $this->leadLimit > 0 ? $query->limit($this->leadLimit)->get() : $query->get();

        if ($leads->isEmpty()) {
            $this->showConfirm = false;
            return;
        }

        $broadcast = Broadcast::create([
            'company_id'          => $user->company_id,
            'user_id'             => $user->id,
            'name'                => $this->broadcastName ?: 'Broadcast ' . now()->format('d/m/Y H:i'),
            'message_template_id' => $this->templateId,
            'lead_filters'        => ['status_ids' => $this->filterStatusIds, 'sources' => $this->filterSources],
            'status'              => 'queued',
            'total_count'         => $leads->count(),
        ]);

        $startAt   = $this->resolveStartAt();
        $batchSize = ($this->batchEnabled && $this->batchSize > 0) ? $this->batchSize : $leads->count();

        foreach ($leads->chunk($batchSize) as $dayIndex => $chunk) {
            $scheduledFor = $startAt->copy()->addDays($dayIndex)->setTimezone('UTC');

            foreach ($chunk as $lead) {
                ScheduledMessage::create([
                    'broadcast_id'        => $broadcast->id,
                    'lead_id'             => $lead->id,
                    'user_id'             => $lead->user_id,
                    'message_template_id' => $this->templateId,
                    'channel'             => 'whatsapp',
                    'message_body'        => '',
                    'status'              => 'pending',
                    'scheduled_for'       => $scheduledFor,
                ]);
            }
        }

        $this->broadcastName   = '';
        $this->templateId      = null;
        $this->filterStatusIds = [];
        $this->filterSources   = [];
        $this->leadLimit       = 0;
        $this->batchEnabled    = false;
        $this->batchSize       = 100;
        $this->startDate       = now(self::TIMEZONE)->format('Y-m-d');
        $this->startTime       = now(self::TIMEZONE)->format('H:i');
        $this->showConfirm     = false;
        $this->previewCount    = 0;
        $this->previewSchedule = [];
        $this->successMessage  = "✓ Broadcast creado: {$leads->count()} mensajes en cola.";

        $this->loadHistory();
    }

    private function buildLeadQuery()
    {
        $user  = Auth::user();
        $query = Lead::where('company_id', $user->company_id)
            ->where('do_not_contact', false)
            ->where('whatsapp_consent', true)
            ->whereNotNull('phone')
            ->orderBy('created_at'); // FIFO: primeros importados, primeros en recibir

        if ($user->isAgent()) {
            $query->where('user_id', $user->id);
        }

        if (! empty($this->filterStatusIds)) {
            $query->whereIn('lead_status_id', $this->filterStatusIds);
        }

        if (! empty($this->filterSources)) {
            $query->whereIn('source', $this->filterSources);
        }

        // Excluir leads que ya recibieron esta plantilla (enviada o en cola)
        if ($this->templateId) {
            $query->whereDoesntHave('scheduledMessages', function ($q) {
                $q->where('message_template_id', $this->templateId)
                  ->whereIn('status', ['sent', 'pending']);
            });
        }

        return $query;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
