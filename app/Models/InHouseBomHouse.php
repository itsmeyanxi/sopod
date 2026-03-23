<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InHouseBomHouse extends Model
{
    protected $table = 'inhouse_bom_houses';

    protected $fillable = [
        'bom_id',
        'house_number',
        'house_name',
        'loading_qty',
        'livability',
        'alw',
        'fcr',
        'age_days',
        'bpi',
        'harvest_qty',
        'total_kg',
        'feed_req_kg',
        'doc_cost',
        'materials',
        'total_cost',
        'cost_per_kg',
    ];

    protected $casts = [
        'materials'   => 'array',
        'loading_qty' => 'float',
        'livability'  => 'float',
        'alw'         => 'float',
        'fcr'         => 'float',
        'harvest_qty' => 'float',
        'total_kg'    => 'float',
        'feed_req_kg' => 'float',
        'doc_cost'    => 'float',
        'total_cost'  => 'float',
        'cost_per_kg' => 'float',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(InHouseBom::class, 'bom_id');
    }

    // ── Compute totals from materials JSON ────────────────────────────────────

    public function computeTotals(): array
    {
        $loading  = (float) ($this->loading_qty ?? 0);
        $docCost  = (float) ($this->doc_cost    ?? 24.00);
        $totalKg  = (float) ($this->total_kg    ?? 0);

        $docAmount = $loading * $docCost;
        $matTotal  = 0;

        foreach ($this->materials ?? [] as $mat) {
            $matTotal += (float) ($mat['qty_kg'] ?? 0) * (float) ($mat['cost'] ?? 0);
        }

        $totalCost  = $docAmount + $matTotal;
        $costPerKg  = $totalKg > 0 ? $totalCost / $totalKg : 0;

        return [
            'total_cost'  => round($totalCost, 4),
            'cost_per_kg' => round($costPerKg, 4),
        ];
    }
}