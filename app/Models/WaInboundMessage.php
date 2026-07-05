<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaInboundMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
