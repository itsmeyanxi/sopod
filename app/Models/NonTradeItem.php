<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonTradeItem extends Model
{
    protected $fillable = ['name', 'item_code', 'group', 'brand', 'unit', 'trading_uom', 'conversion', 'status', 'supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
