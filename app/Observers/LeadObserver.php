<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\FollowUpGenerator;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        app(FollowUpGenerator::class)->generateForLead($lead, 'lead_created');
    }

    public function updated(Lead $lead): void
    {
        if ($lead->isDirty('lead_status_id') && $lead->lead_status_id !== null) {
            app(FollowUpGenerator::class)->generateForLead($lead, 'status_change');
        }
    }
}
