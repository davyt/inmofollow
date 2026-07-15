<?php

namespace App\Models;

use App\Models\Concerns\LogsBasicActivity;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    use LogsBasicActivity;
    
    protected $guarded = [];
    
    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'step_data'     => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function sequenceStep()
    {
        return $this->belongsTo(SequenceStep::class);
    }

    public function messageTemplate()
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}