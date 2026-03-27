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
        'approved',
        'approved_by',
        'approved_at',
        'parent_bom_id',
        'extension_number',
    ];

    protected $casts = [
        'cycle_date'   => 'date',
        'approved'     => 'boolean',
        'approved_at'  => 'datetime',
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

    public function feedUsages(): HasMany
    {
        return $this->hasMany(DailyFeedUsage::class, 'bom_id')->orderBy('usage_date', 'desc');
    }

    public function parentBom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_bom_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_bom_id')->orderBy('extension_number');
    }

    public function isExtension(): bool
    {
        return $this->parent_bom_id !== null;
    }

    /**
     * Get the root BOM (top-level parent)
     */
    public function getRootBom(): self
    {
        return $this->isExtension() ? $this->parentBom->getRootBom() : $this;
    }

    /**
     * Combined cost: this BOM + all approved extensions
     */
    public function getCombinedCostAttribute(): float
    {
        $cost = $this->total_cost;
        foreach ($this->extensions as $ext) {
            if ($ext->approved) {
                $cost += $ext->total_cost;
            }
        }
        return $cost;
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
        $year   = now()->year;
        $prefix = 'BOM-' . $year . '-';

        // Exclude extension refs (contain -EXT) from the numbering
        $maxRef = static::where('cycle_ref', 'like', $prefix . '%')
            ->where('cycle_ref', 'NOT LIKE', '%-EXT%')
            ->orderByRaw('CAST(SUBSTRING(cycle_ref, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('cycle_ref');

        $next = 1;
        if ($maxRef) {
            $num  = (int) substr($maxRef, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next extension ref for a parent BOM
     * e.g. BOM-2026-0001-EXT1, BOM-2026-0001-EXT2
     */
    public static function nextExtensionRef(self $parentBom): string
    {
        $baseRef = $parentBom->isExtension()
            ? $parentBom->parentBom->cycle_ref
            : $parentBom->cycle_ref;

        $maxExt = static::where('parent_bom_id', $parentBom->isExtension() ? $parentBom->parent_bom_id : $parentBom->id)
            ->max('extension_number') ?? 0;

        $nextNum = $maxExt + 1;

        return $baseRef . '-EXT' . $nextNum;
    }
}