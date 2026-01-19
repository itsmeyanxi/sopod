<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ARLedger extends Model
{
    use HasFactory;

    protected $table = 'ar_ledger';

    protected $fillable = [
        'customer_code',
        'invoice_id',
        'transaction_type',
        'debit',
        'credit',
        'transaction_date',
        'reference_no',
        'remarks',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Get the customer associated with this ledger entry
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    /**
     * Get the invoice associated with this ledger entry
     */
    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    /**
     * Scope for payment transactions
     */
    public function scopePayments($query)
    {
        return $query->where('transaction_type', 'Payment');
    }

    /**
     * Scope for invoice transactions
     */
    public function scopeInvoices($query)
    {
        return $query->where('transaction_type', 'Invoice');
    }
}