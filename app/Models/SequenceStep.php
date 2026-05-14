<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SequenceStep extends Model
{
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