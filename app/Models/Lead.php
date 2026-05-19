<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use App\Models\Concerns\LogsBasicActivity;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasDefaultCompany, LogsBasicActivity;
    
    protected $guarded = [];

    protected $casts = [
        'whatsapp_consent' => 'boolean',
        'email_consent' => 'boolean',
        'do_not_contact' => 'boolean',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
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

    public function notes()
    {
        return $this->hasMany(LeadNote::class);
    }

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledMessage::class);
    }
}