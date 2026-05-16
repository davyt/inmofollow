<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasDefaultCompany;

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
}