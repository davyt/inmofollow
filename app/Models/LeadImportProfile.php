<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;

class LeadImportProfile extends Model
{
    use HasDefaultCompany;

    protected $guarded = [];

    protected $casts = [
        'mapping'                   => 'array',
        'default_whatsapp_consent' => 'boolean',
        'default_email_consent'    => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
