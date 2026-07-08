<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadListing extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attributes'   => 'array',
        'asking_price' => 'decimal:2',
        'm2_covered'   => 'decimal:2',
        'm2_total'     => 'decimal:2',
        'is_primary'   => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function formattedPrice(): string
    {
        if (! $this->asking_price) return '';
        return number_format($this->asking_price, 0, ',', '.') . ' ' . ($this->price_currency ?? 'USD');
    }

    public function summary(): string
    {
        $parts = array_filter([
            $this->property_type,
            $this->operation ? strtolower($this->operation) : null,
            $this->asking_price ? $this->formattedPrice() : null,
            $this->bedrooms ? "{$this->bedrooms} dorm." : null,
            $this->m2_covered ? "{$this->m2_covered} m²" : null,
        ]);
        return implode(' · ', $parts);
    }
}
