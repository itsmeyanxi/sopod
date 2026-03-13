<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storage extends Model
{
    protected $fillable = [
        'storage_code',
        'storage_name',
        'warehouse_id',
        'location',
        'description',
        'temperature_controlled',
        'capacity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
