<?php

namespace App\Support;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class Activity
{
    public static function log(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = []
    ): void {
        app(ActivityLogger::class)->log(
            event: $event,
            description: $description,
            subject: $subject,
            properties: $properties
        );
    }
}
