<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InHouseBom extends Model
{
    protected $table = 'inhouse_boms';

    protected $fillable = [
        'cycle_ref',
        'cycle_date',
        'grower',
        'notes',
        'num_houses',
        'status',
        'created_by',
    ];

    protected $casts = [
        'cycle_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function houses(): HasMany
    {
        return $this->hasMany(InHouseBomHouse::class, 'bom_id')->orderBy('house_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Computed Accessors ────────────────────────────────────────────────────

    public function getTotalLoadingAttribute(): float
    {
        return $this->houses->sum('loading_qty');
    }

    public function getTotalHarvestAttribute(): float
    {
        return $this->houses->sum('harvest_qty');
    }

    public function getTotalCostAttribute(): float
    {
        return $this->houses->sum('total_cost');
    }

    public function getAvgCostPerKgAttribute(): ?float
    {
        $totalKg = $this->houses->sum('total_kg');
        return $totalKg > 0 ? $this->total_cost / $totalKg : null;
    }

    public function getAvgFcrAttribute(): ?float
    {
        $houses = $this->houses->whereNotNull('fcr');
        return $houses->count() > 0 ? $houses->avg('fcr') : null;
    }

    // ── Auto-generate cycle_ref ───────────────────────────────────────────────

    public static function nextCycleRef(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'BOM-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}