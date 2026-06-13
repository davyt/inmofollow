<?php

namespace App\Models;

use App\Models\Concerns\LogsBasicActivity;
use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasDefaultCompany, LogsBasicActivity;

    protected $guarded = [];

    protected $casts = [
        'active'                  => 'boolean',
        'meta_template_variables' => 'array',
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