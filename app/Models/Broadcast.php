<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasDefaultCompany;

    protected $guarded = [];

    protected $casts = [
        'lead_filters'  => 'array',
        'completed_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messageTemplate()
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledMessage::class);
    }
}
