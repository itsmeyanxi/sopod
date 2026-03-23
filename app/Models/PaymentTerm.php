<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'net_days',
        'discount_pct',
        'discount_days',
    ];

    protected $casts = [
        'net_days' => 'integer',
        'discount_pct' => 'decimal:2',
        'discount_days' => 'integer',
    ];

    public function getSuppliersUsingCountAttribute()
    {
        return Supplier::where('terms', $this->description)->count();
    }
}
