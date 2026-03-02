<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsPayableInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'apv_no',
        'request_for_payment_id',
        'apv_date',
        'payment_type',
        'vendor_code',
        'vendor_name',
        'vendor_address',
        'vendor_tin',
        'document_date',
        'payment_terms',
        'due_date',
        'reference_no',
        'purchase_order_no',
        'currency',
        'forex_rate',
        'particulars',
        'item_code',
        'cost_center',
        'account_code',
        'account_name',
        'total',
        'downpayment_amount',
        'total_before_vat',
        'vat_amount',
        'total_after_vat',
        'w_tax_amount',
        'grand_total',
        'prepared_by',
        'reviewed_by',
        'remarks',
        'status',
        'approval_stage',
        'department_head_approved_by',
        'department_head_approved_at',
        'department_head_approved_latitude',
        'department_head_approved_longitude',
        'department_head_approved_location',
        'approved_by',
        'approved_at',
        'approved_latitude',
        'approved_longitude',
        'approved_location',
        'rejection_reason',
        'created_by',
        'cash_advance_request_id',
        'reimbursement_form_id',
        'reference_type',
    ];

    protected $casts = [
        'apv_date' => 'datetime',
        'document_date' => 'datetime',
        'due_date' => 'datetime',
        'total' => 'decimal:2',
        'downpayment_amount' => 'decimal:2',
        'total_before_vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_after_vat' => 'decimal:2',
        'w_tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'forex_rate' => 'decimal:4',
        'department_head_approved_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requestForPayment()
    {
        return $this->belongsTo(RequestForPayment::class, 'request_for_payment_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departmentHeadApprover()
    {
        return $this->belongsTo(User::class, 'department_head_approved_by');
    }

    public function cashAdvanceRequest()
    {
        return $this->belongsTo(CashAdvanceRequest::class);
    }

    public function reimbursementForm()
    {
        return $this->belongsTo(ReimbursementForm::class);
    }
}
