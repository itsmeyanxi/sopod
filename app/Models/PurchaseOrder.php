<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_no',
        'purchase_request_id',
        'company',
        'supplier',
        'supplier_id',
        'supplier_address',
        'consignee',
        'consignee_address',
        'delivery_address',
        'order_date',
        'expected_delivery_date',
        'payment_terms',
        'location',
        'house',
        'pr_no',
        'lc_price',
        'currency',
        'exchange_rate',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'lc_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationship with PurchaseOrderItems
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with PurchaseRequest
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    /**
     * Relationship with Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Relationship with User who approved
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if PO is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if PO is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if PO is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
