<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'supplier_id',
        'supplier_name',
        'item_no',
        'item_code',
        'date_needed',
        'qty',
        'uom',
        'description',
        'brand',
        'unit_price',
        'vat',
        'tax_code',
        'tax',
        'total',
        'note',
    ];

    protected $casts = [
        'qty'        => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat'        => 'boolean',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    /**
     * Relationship with PurchaseOrder
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    /**
     * Relationship with PurchaseRequestItem
     */
    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id', 'id');
    }

    /**
     * Relationship with Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierModel()
{
    return $this->belongsTo(\App\Models\Supplier::class, 'supplier_id');
}
}
