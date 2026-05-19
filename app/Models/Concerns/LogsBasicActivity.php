<?php

namespace App\Models\Concerns;

use App\Support\Activity;

trait LogsBasicActivity
{
    protected static function bootLogsBasicActivity(): void
    {
        static::created(function ($model) {
            Activity::log(
                event: static::activityName('created'),
                description: static::activityDescription('creó', $model),
                subject: $model
            );
        });

        static::updated(function ($model) {
            Activity::log(
                event: static::activityName('updated'),
                description: static::activityDescription('actualizó', $model),
                subject: $model,
                properties: [
                    'changed' => array_keys($model->getChanges()),
                ]
            );
        });

        static::deleted(function ($model) {
            Activity::log(
                event: static::activityName('deleted'),
                description: static::activityDescription('eliminó', $model),
                subject: $model
            );
        });
    }

    protected static function activityName(string $action): string
    {
        return str(class_basename(static::class))
            ->snake()
            ->append("_{$action}")
            ->toString();
    }

    protected static function activityDescription(string $action, $model): string
    {
        return 'Se ' . $action . ' ' . class_basename($model) . ' #' . $model->getKey() . '.';
    }
}
