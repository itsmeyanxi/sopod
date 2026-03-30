<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetClass extends Model
{
    protected $table = 'asset_classes';

    protected $fillable = [
        'asset_group',
        'asset_class',
        'useful_life_months',
        'gl_account',
        'depreciation_account',
        'dep_type',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'useful_life_months' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
