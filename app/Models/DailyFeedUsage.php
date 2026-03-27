<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyFeedUsage extends Model
{
    protected $fillable = [
        'bom_id',
        'house_number',
        'usage_date',
        'materials_used',
        'notes',
        'logged_by',
    ];

    protected $casts = [
        'usage_date'     => 'date',
        'materials_used' => 'array',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(InHouseBom::class, 'bom_id');
    }
}
