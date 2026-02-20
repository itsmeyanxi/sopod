<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonTradeItem extends Model
{
    protected $fillable = ['name', 'unit', 'supplier_id', 'item_code', 'account', 'vendor_code'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
