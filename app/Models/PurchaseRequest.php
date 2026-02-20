<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_no',
        'company',
        'requisitioner',
        'department',
        'supplier',
        'supplier_id',
        'terms',
        'address',
        'delivery_address',
        'contact_person',
        'date_of_request',
        'date_needed',
        'type_of_request',
        'with_budget',
        'charge_to',
        'contact_number',
        'reason_for_requisition',
        'status',
        'approved_by',
        'approved_at',
        'approved_latitude',
        'approved_longitude',
        'approved_location',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'date_of_request' => 'datetime',
        'date_needed' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationship with PurchaseRequestItems
     */
    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class, 'purchase_request_id', 'id');
    }

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Check if PR is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if PR is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if PR is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
