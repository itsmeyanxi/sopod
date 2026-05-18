<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRateLog extends Model
{
    protected $fillable = [
        'currency_id',
        'currency_code',
        'old_rate',
        'new_rate',
        'updated_by',
    ];

    protected $casts = [
        'old_rate' => 'decimal:4',
        'new_rate' => 'decimal:4',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
