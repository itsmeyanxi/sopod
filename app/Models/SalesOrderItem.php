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
        'batch_status',
        'delivery_batch',           
        'request_delivery_date',
        'note',  
    ];

    protected $casts = [
        'request_delivery_date' => 'date', 
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    
    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class, 'sales_order_item_id');
    }
}