<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationRun extends Model
{
    protected $fillable = [
        'run_number', 'period', 'description', 'asset_groups',
        'depreciation_entries', 'total_debit', 'total_credit',
        'status', 'jv_number', 'posted_at', 'prepared_by',
        'approved_by', 'created_by',
    ];

    protected $casts = [
        'asset_groups'         => 'array',
        'depreciation_entries' => 'array',
        'total_debit'          => 'decimal:2',
        'total_credit'         => 'decimal:2',
        'posted_at'            => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'Posted');
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function getPeriodLabelAttribute(): string
    {
        if (!$this->period) return '—';
        $parts = explode('-', $this->period);
        if (count($parts) === 2) {
            return date('F Y', mktime(0, 0, 0, (int) $parts[1], 1, (int) $parts[0]));
        }
        return $this->period;
    }
}
