<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\ScheduledMessage;
use App\Models\Sequence;
use Illuminate\Support\Carbon;

class FollowUpGenerator
{
    public function generateForLead(Lead $lead): int
    {
        if ($lead->do_not_contact) {
            return 0;
        }

        if (! $lead->lead_status_id) {
            return 0;
        }

        $baseQuery = Sequence::query()
            ->where('active', true)
            ->where('lead_status_id', $lead->lead_status_id)
            ->where(function ($query) use ($lead) {
                $query
                    ->whereNull('company_id')
                    ->orWhere('company_id', $lead->company_id);
            });
        
        $sequence = (clone $baseQuery)
            ->where('scope', 'personal')
            ->where('user_id', $lead->user_id)
            ->first();
        
        if (! $sequence) {
            $sequence = (clone $baseQuery)
                ->where('scope', 'global')
                ->first();
        }

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
            $template = $step->messageTemplate;

            if (! $template || ! $template->active) {
                continue;
            }

            if ($step->channel === 'whatsapp' && ! $lead->whatsapp_consent) {
                continue;
            }

            if ($step->channel === 'email' && ! $lead->email_consent) {
                continue;
            }

            $alreadyExists = ScheduledMessage::query()
                ->where('lead_id', $lead->id)
                ->where('sequence_step_id', $step->id)
                ->whereIn('status', ['pending', 'sent'])
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            ScheduledMessage::create([
                'lead_id' => $lead->id,
                'sequence_id' => $sequence->id,
                'sequence_step_id' => $step->id,
                'message_template_id' => $template->id,
                'user_id' => $lead->user_id,
                'channel' => $step->channel,
                'message_body' => $this->renderTemplate($template->body, $lead),
                'status' => 'pending',
                'scheduled_for' => Carbon::now()->addDays((int) $step->day_offset),
            ]);

            $created++;
        }

        $nextMessage = ScheduledMessage::query()
            ->where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->orderBy('scheduled_for')
            ->first();

        if ($nextMessage) {
            $lead->update([
                'next_follow_up_at' => $nextMessage->scheduled_for,
            ]);
        }

        return $created;
    }

    private function renderTemplate(string $body, Lead $lead): string
    {
        return str_replace(
            [
                '{{nombre}}',
                '{{zona}}',
                '{{tipo_propiedad}}',
                '{{agente}}',
            ],
            [
                $lead->name ?? '',
                $lead->zone ?? '',
                $lead->property_type ?? '',
                $lead->user?->name ?? '',
            ],
            $body
        );
    }
}
