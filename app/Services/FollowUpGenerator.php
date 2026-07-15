<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadListing;
use App\Models\LeadStatus;
use App\Models\ScheduledMessage;
use App\Models\Sequence;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Carbon;

class FollowUpGenerator
{
    public function generateForLead(Lead $lead, string $triggerType = 'status_change'): int
    {
        if ($lead->do_not_contact) {
            return 0;
        }

        if ($triggerType === 'status_change' && ! $lead->lead_status_id) {
            return 0;
        }

        $sequence = $this->resolveSequence($lead, $triggerType);

        if (! $sequence) {
            return 0;
        }

        $steps = $sequence->steps()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('day_offset')
            ->get();

        if ($steps->isEmpty()) {
            return 0;
        }

        $created = 0;

        foreach ($steps as $step) {
            $stepType = $step->step_type ?? 'send_template';

            // Idempotency check
            $alreadyExists = ScheduledMessage::query()
                ->where('lead_id', $lead->id)
                ->where('sequence_step_id', $step->id)
                ->whereIn('status', ['pending', 'sent'])
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $dayOffset = (int) $step->day_offset;
            $stepData  = $step->step_data ?? [];

            match ($stepType) {
                'send_template'          => $created += $this->handleSendTemplate($lead, $sequence, $step, $dayOffset),
                'send_message'           => $created += $this->handleSendMessage($lead, $sequence, $step, $stepData, $dayOffset),
                'update_status'          => $created += $this->handleUpdateStatus($lead, $sequence, $step, $stepData, $dayOffset),
                'assign_agent'           => $created += $this->handleAssignAgent($lead, $sequence, $step, $stepData, $dayOffset),
                'send_report'            => $created += $this->handleSendReport($lead, $sequence, $step, $stepData, $dayOffset),
                'send_template_to_agent' => $created += $this->handleSendTemplateToAgent($lead, $sequence, $step, $stepData, $dayOffset),
                default                  => null,
            };
        }

        // Update next follow-up date
        $nextMessage = ScheduledMessage::query()
            ->where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->orderBy('scheduled_for')
            ->first();

        if ($nextMessage) {
            $lead->updateQuietly(['next_follow_up_at' => $nextMessage->scheduled_for]);
        }

        if ($created > 0) {
            Activity::log(
                event: 'followups_generated',
                description: "Se generaron {$created} acción(es) programada(s) para el lead.",
                subject: $lead,
                properties: ['created_messages' => $created],
            );
        }

        return $created;
    }

    // -------------------------------------------------------------------------

    private function resolveSequence(Lead $lead, string $triggerType): ?Sequence
    {
        $baseQuery = Sequence::query()
            ->where('active', true)
            ->where('trigger_type', $triggerType)
            ->where(function ($q) use ($lead) {
                $q->whereNull('company_id')->orWhere('company_id', $lead->company_id);
            })
            ->where(function ($q) use ($lead) {
                $q->whereNull('trigger_source')
                  ->orWhere('trigger_source', $lead->source);
            });

        if ($triggerType === 'status_change') {
            $baseQuery->where('lead_status_id', $lead->lead_status_id);
        }

        return (clone $baseQuery)->where('scope', 'personal')->where('user_id', $lead->user_id)->first()
            ?? (clone $baseQuery)->where('scope', 'global')->first();
    }

    private function handleSendTemplate(Lead $lead, Sequence $sequence, $step, int $dayOffset): int
    {
        $template = $step->messageTemplate;

        if (! $template || ! $template->active) {
            return 0;
        }

        if ($step->channel === 'whatsapp' && ! $lead->whatsapp_consent) {
            return 0;
        }

        if ($step->channel === 'email' && ! $lead->email_consent) {
            return 0;
        }

        ScheduledMessage::create([
            'lead_id'             => $lead->id,
            'sequence_id'         => $sequence->id,
            'sequence_step_id'    => $step->id,
            'message_template_id' => $template->id,
            'user_id'             => $lead->user_id,
            'channel'             => $step->channel,
            'step_type'           => 'send_template',
            'message_body'        => $this->renderTemplate($template->body, $lead),
            'status'              => 'pending',
            'scheduled_for'       => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    private function handleSendMessage(Lead $lead, Sequence $sequence, $step, array $stepData, int $dayOffset): int
    {
        $body = $stepData['message'] ?? '';

        if (! $body) {
            return 0;
        }

        if (! $lead->whatsapp_consent) {
            return 0;
        }

        ScheduledMessage::create([
            'lead_id'          => $lead->id,
            'sequence_id'      => $sequence->id,
            'sequence_step_id' => $step->id,
            'user_id'          => $lead->user_id,
            'channel'          => 'whatsapp',
            'step_type'        => 'send_message',
            'message_body'     => $this->renderTemplate($body, $lead),
            'status'           => 'pending',
            'scheduled_for'    => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    private function handleUpdateStatus(Lead $lead, Sequence $sequence, $step, array $stepData, int $dayOffset): int
    {
        $statusId = $stepData['status_id'] ?? null;
        if (! $statusId) return 0;

        if ($dayOffset === 0) {
            $lead->updateQuietly(['lead_status_id' => $statusId]);
            return 1;
        }

        ScheduledMessage::create([
            'lead_id'          => $lead->id,
            'sequence_id'      => $sequence->id,
            'sequence_step_id' => $step->id,
            'channel'          => 'action',
            'step_type'        => 'update_status',
            'step_data'        => $stepData,
            'message_body'     => '',
            'status'           => 'pending',
            'scheduled_for'    => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    private function handleAssignAgent(Lead $lead, Sequence $sequence, $step, array $stepData, int $dayOffset): int
    {
        $agentId = $stepData['agent_id'] ?? null;
        if (! $agentId) return 0;

        if ($dayOffset === 0) {
            $lead->updateQuietly(['user_id' => $agentId]);
            return 1;
        }

        ScheduledMessage::create([
            'lead_id'          => $lead->id,
            'sequence_id'      => $sequence->id,
            'sequence_step_id' => $step->id,
            'channel'          => 'action',
            'step_type'        => 'assign_agent',
            'step_data'        => $stepData,
            'message_body'     => '',
            'status'           => 'pending',
            'scheduled_for'    => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    private function handleSendTemplateToAgent(Lead $lead, Sequence $sequence, $step, array $stepData, int $dayOffset): int
    {
        $template = $step->messageTemplate;

        if (! $template || ! $template->active) {
            return 0;
        }

        if (empty($stepData['agent_id'])) {
            return 0;
        }

        ScheduledMessage::create([
            'lead_id'             => $lead->id,
            'sequence_id'         => $sequence->id,
            'sequence_step_id'    => $step->id,
            'message_template_id' => $template->id,
            'user_id'             => $lead->user_id,
            'channel'             => 'agent_template',
            'step_type'           => 'send_template_to_agent',
            'step_data'           => $stepData,
            'message_body'        => '',
            'status'              => 'pending',
            'scheduled_for'       => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    private function handleSendReport(Lead $lead, Sequence $sequence, $step, array $stepData, int $dayOffset): int
    {
        // The report goes to the agent, not the lead — no consent check needed.
        // The agent receiving is determined at send time (after optional reassign).
        ScheduledMessage::create([
            'lead_id'          => $lead->id,
            'sequence_id'      => $sequence->id,
            'sequence_step_id' => $step->id,
            'user_id'          => $lead->user_id,
            'channel'          => 'agent_report',
            'step_type'        => 'send_report',
            'step_data'        => $stepData,
            'message_body'     => '',
            'status'           => 'pending',
            'scheduled_for'    => Carbon::now()->addDays($dayOffset),
        ]);

        return 1;
    }

    // -------------------------------------------------------------------------

    private function renderTemplate(string $body, Lead $lead): string
    {
        return str_replace(
            ['{{nombre}}', '{{zona}}', '{{tipo_propiedad}}', '{{agente}}'],
            [$lead->name ?? '', $lead->zone ?? '', $lead->property_type ?? '', $lead->user?->name ?? ''],
            $body
        );
    }
}
