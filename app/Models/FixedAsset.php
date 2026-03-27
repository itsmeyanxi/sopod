<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FixedAsset extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('not_disposed', function (Builder $builder) {
            $builder->whereNull('disposal_date');
        });
    }

    protected $table = 'fixed_assets';

    protected $fillable = [
        'serial_engine_no',
        'asset_group',
        'date_posted',
        'acquisition_date',
        'dep_start_date',
        'dep_end_date',
        'disposal_date',
        'disposed_by',
        'disposal_reason',
        'dep_type',
        'reference_apv_jv',
        'reference_reversal',
        'vendor_name',
        'plate_no',
        'assigned_person',
        'employee_name',
        'quantity',
        'asset_description',
        'asset_code',
        'gl_account',
        'asset_class',
        'cost_center_name',
        'cost_center_code',
        'depreciation_account',
        'cost',
        'year_in_service',
        'year_end_service',
        'useful_life_years',
        'useful_life_months',
        'remaining_life_months',
        'salvage_value',
        'yearly_depreciation',
        'monthly_depreciation',
        'additions',
        'reclass_affiliates',
        'disposal_amount',
        'accumulated_depreciation',
        'net_book_value',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_posted' => 'date',
        'acquisition_date' => 'date',
        'dep_start_date' => 'date',
        'dep_end_date' => 'date',
        'disposal_date' => 'date',
        'cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'yearly_depreciation' => 'decimal:2',
        'monthly_depreciation' => 'decimal:2',
        'additions' => 'decimal:2',
        'reclass_affiliates' => 'decimal:2',
        'disposal_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'useful_life_years' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getFormattedAssetGroupAttribute()
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->asset_group));
    }

    public function getIsFullyDepreciatedAttribute()
    {
        return $this->remaining_life_months == 0;
    }

    public function getIsDisposedAttribute()
    {
        return $this->disposal_date !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('disposal_date')->where('status', 'active');
    }

    public function scopeDisposed($query)
    {
        return $query->withoutGlobalScope('not_disposed')->whereNotNull('disposal_date');
    }

    public function scopeWithDisposed($query)
    {
        return $query->withoutGlobalScope('not_disposed');
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('asset_group', $group);
    }
}
