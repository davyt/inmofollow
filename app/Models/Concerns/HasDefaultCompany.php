<?php

namespace App\Models\Concerns;

trait HasDefaultCompany
{
    protected static function bootHasDefaultCompany(): void
    {
        static::creating(function ($model) {
            if (! $model->company_id) {
                $model->company_id = config('inmofollow.default_company_id', 1);
            }
        });
    }
}
