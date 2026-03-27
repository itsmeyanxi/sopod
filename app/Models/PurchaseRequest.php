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
        'bom_id',
        'bom_cycle_ref',
        'bom_total_cost',
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
        'date_of_request' => 'datetime',
        'date_needed' => 'datetime',
        'approved_at' => 'datetime',
        'department_head_approved_at' => 'datetime',
        'management_approved_at' => 'datetime',
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

    public function bom()
    {
        return $this->belongsTo(InHouseBom::class, 'bom_id');
    }

    /**
     * Relationship with User who approved
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship with User who department head approved
     */
    public function departmentHeadApprover()
    {
        return $this->belongsTo(User::class, 'department_head_approved_by');
    }

    /**
     * Relationship with User who management (GM/CFO) approved
     */
    public function managementApprover()
    {
        return $this->belongsTo(User::class, 'management_approved_by');
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
