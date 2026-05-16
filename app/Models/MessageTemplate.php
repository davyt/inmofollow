<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasDefaultCompany;

class MessageTemplate extends Model
{
    use HasDefaultCompany;
    
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}