<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeItem extends Model
{
    protected $fillable = [
        'name',
        'vendor_code',
        'account',
        'local_or_import',
        'supplier_id',
        'item_code',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
