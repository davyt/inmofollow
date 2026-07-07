<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class AiAgent extends Model
{
    use HasDefaultCompany;

    protected $guarded = [];

    protected $casts = [
        'active'    => 'boolean',
        'auto_send' => 'boolean',
        'api_key'   => 'encrypted',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
