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
        'approval_user_id',
        'approval_date',
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
        'approval_date' => 'datetime',
    ];

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with AccountsPayableInvoice
     */
    public function accountsPayableInvoice()
    {
        return $this->belongsTo(AccountsPayableInvoice::class, 'accounts_payable_invoice_id');
    }

    /**
     * Relationship with User who approved (system approval)
     */
    public function approvalUser()
    {
        return $this->belongsTo(User::class, 'approval_user_id');
    }
}
