<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function aiAgent()
    {
        return $this->belongsTo(AiAgent::class);
    }
}
