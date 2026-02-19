<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate_to_php',
        'updated_by',
    ];

    protected $casts = [
        'rate_to_php' => 'decimal:4',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function phpRate(string $code): float
    {
        if ($code === 'PHP') return 1.0;
        $currency = static::where('code', $code)->first();
        return $currency ? (float) $currency->rate_to_php : 1.0;
    }
}
