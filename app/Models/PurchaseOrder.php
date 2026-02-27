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
        'supplier',          // string column (supplier name text)
        'supplier_id',       // FK to suppliers table
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
        'quotation',
        'status',
        'approval_stage',
        'approved_by',
        'approved_at',
        'approved_latitude',
        'approved_longitude',
        'approved_location',
        'rejection_reason',
        'created_by',
        'department_head_approved_by',
        'department_head_approved_at',
        'department_head_approved_latitude',
        'department_head_approved_longitude',
        'department_head_approved_location',
        'management_approved_by',
        'management_approved_at',
        'management_approved_latitude',
        'management_approved_longitude',
        'management_approved_location',
    ];

    protected $casts = [
        'order_date'                  => 'datetime',
        'expected_delivery_date'      => 'datetime',
        'lc_price'                    => 'decimal:2',
        'exchange_rate'               => 'decimal:4',
        'approved_at'                 => 'datetime',
        'department_head_approved_at' => 'datetime',
        'management_approved_at'      => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    /**
     * FIX: Renamed from supplier() → supplierModel() to avoid conflict with
     * the 'supplier' string column. Calling ->supplier on an instance now
     * returns the column value, not the relationship.
     *
     * Use $po->supplierModel to get the Supplier Eloquent object.
     * Use $po->supplier   to get the plain text supplier name string.
     */
    public function supplierModel()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departmentHeadApprover()
    {
        return $this->belongsTo(User::class, 'department_head_approved_by');
    }

    public function managementApprover()
    {
        return $this->belongsTo(User::class, 'management_approved_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPending():  bool { return $this->status === 'pending';  }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}