<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasDefaultCompany;

class LeadStatus extends Model
{
    use HasDefaultCompany;
    protected $guarded = [];
}
