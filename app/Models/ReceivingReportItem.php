<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingReportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_report_id',
        'item_id',
        'sales_order_item_id',
        'item_code',
        'item_description',
        'brand',
        'item_category',
        'quantity',
        'original_quantity',
        'remaining_quantity',
        'uom',
        'unit_price',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'original_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function receivingReport()
    {
        return $this->belongsTo(ReceivingReport::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}