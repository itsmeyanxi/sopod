<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AccountsPayableInvoice;

class RequestForPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfp_no',
        'purchase_order_id',
        'company',
        'payment_methods',
        'date',
        'due_date',
        'payee',
        'amount',
        'particulars',
        'bank',
        'apv_no',
        'cv_no',
        'requested_by',
        'checked_by',
        'status',
        'approval_stage',
        'department_head_approved_by',
        'department_head_approved_at',
        'department_head_approved_latitude',
        'department_head_approved_longitude',
        'department_head_approved_location',
        'accounting_approved_by',
        'accounting_approved_at',
        'accounting_approved_latitude',
        'accounting_approved_longitude',
        'accounting_approved_location',
        'approved_by',
        'approved_at',
        'approved_latitude',
        'approved_longitude',
        'approved_location',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'due_date' => 'datetime',
        'amount' => 'decimal:2',
        'payment_methods' => 'array',
        'department_head_approved_at' => 'datetime',
        'accounting_approved_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departmentHeadApprover()
    {
        return $this->belongsTo(User::class, 'department_head_approved_by');
    }

    public function accountingApprover()
    {
        return $this->belongsTo(User::class, 'accounting_approved_by');
    }

    public function apvs()
    {
        return $this->hasMany(AccountsPayableInvoice::class, 'request_for_payment_id');
    }

    /**
     * Check if RFP is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if RFP is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if RFP is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
