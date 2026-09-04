<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obra extends Model
{
    use HasDefaultCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(ObraGasto::class);
    }
}
