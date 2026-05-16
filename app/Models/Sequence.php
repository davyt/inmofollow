<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasDefaultCompany;

class Sequence extends Model
{
    use HasDefaultCompany;
    
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