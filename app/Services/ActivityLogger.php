<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? $this->getSubjectLabel($subject) : null,
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function getSubjectLabel(Model $subject): string
    {
        foreach (['name', 'title', 'email', 'note', 'message_body'] as $field) {
            if (! empty($subject->{$field})) {
                return mb_strimwidth((string) $subject->{$field}, 0, 120, '...');
            }
        }

        return class_basename($subject) . ' #' . $subject->getKey();
    }
}
