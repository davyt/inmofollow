<?php

namespace App\Models;

use App\Models\Concerns\LogsBasicActivity;
use Illuminate\Database\Eloquent\Model;

class SequenceStep extends Model
{
    use LogsBasicActivity;
    
    protected $guarded = [];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function messageTemplate()
    {
        return $this->belongsTo(MessageTemplate::class);
    }
}