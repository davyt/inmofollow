<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function steps()
    {
        return $this->hasMany(SequenceStep::class);
    }
}