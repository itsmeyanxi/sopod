<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'item_id',
        'item_code',
        'item_description',
        'brand',
        'item_category',  
        'quantity',
        'unit',           
        'unit_price',
        'total_amount',
    ];

     public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    
}