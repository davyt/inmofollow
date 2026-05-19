<?php

namespace App\Models;

use App\Models\Concerns\LogsBasicActivity;
use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasDefaultCompany, LogsBasicActivity;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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