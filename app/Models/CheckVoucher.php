<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'cv_no',
        'accounts_payable_invoice_id',
        'cv_date',
        'check_date',
        'supplier_code',
        'supplier_name',
        'supplier_address',
        'supplier_tin',
        'check_no',
        'bank',
        'branch',
        'check_amount',
        'payment_date',
        'payment_type',
        'reference_no',
        'apv_no',
        'paid_amount',
        'particulars',
        'journal_entries',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'received_by',
        'date_received',
        'status',
        'approval_stage',
        'accounting_reviewed_by',
        'accounting_reviewed_at',
        'accounting_reviewed_latitude',
        'accounting_reviewed_longitude',
        'accounting_reviewed_location',
        'approval_user_id',
        'approval_date',
        'approved_latitude',
        'approved_longitude',
        'approved_location',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'cv_date' => 'datetime',
        'check_date' => 'datetime',
        'payment_date' => 'datetime',
        'date_received' => 'datetime',
        'check_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'journal_entries' => 'array',
        'accounting_reviewed_at' => 'datetime',
        'approval_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accountsPayableInvoice()
    {
        return $this->belongsTo(AccountsPayableInvoice::class, 'accounts_payable_invoice_id');
    }

    public function approvalUser()
    {
        return $this->belongsTo(User::class, 'approval_user_id');
    }

    public function accountingReviewer()
    {
        return $this->belongsTo(User::class, 'accounting_reviewed_by');
    }
}
