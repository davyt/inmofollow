<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active'          => 'boolean',
        'wa_active'       => 'boolean',
        'wa_access_token' => 'encrypted',
    ];

    public function hasWhatsApp(): bool
    {
        return $this->wa_active
            && ! empty($this->wa_phone_number_id)
            && ! empty($this->wa_access_token);
    }
}
