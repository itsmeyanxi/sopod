<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    use HasFactory;

    protected $table = 'delivery_items';

    protected $fillable = [
        'delivery_id',
        'item_id',
        'sales_order_item_id',  
        'item_code',
        'item_description',
        'brand',
        'item_category',
        'quantity',
        'uom',
        'unit_price',
        'total_amount',
        'delivery_batch',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function delivery()
    {
        return $this->belongsTo(Deliveries::class, 'delivery_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
    // Link back to original SO item
    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }
}