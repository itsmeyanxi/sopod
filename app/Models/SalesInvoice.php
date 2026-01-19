<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'invoice_no',
        'so_id',
        'dr_id',
        'customer_code',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'cwt_amount',
        'net_of_cwt',
        'ar_status',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'invoice_amount' => 'decimal:2',
        'cwt_amount' => 'decimal:2',
        'net_of_cwt' => 'decimal:2',
    ];

    /**
     * Get the customer associated with this invoice
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    /**
     * Get the sales order associated with this invoice
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    /**
     * Get the delivery associated with this invoice
     */
    public function delivery()
    {
        return $this->belongsTo(Deliveries::class, 'dr_id');
    }

    /**
     * Get all AR ledger entries for this invoice
     */
    public function arLedger()
    {
        return $this->hasMany(ARLedger::class, 'invoice_id');
    }

    /**
     * Get the settled amount from AR ledger
     */
    public function getSettledAmountAttribute()
    {
        return $this->arLedger()
            ->where('transaction_type', 'Payment')
            ->sum('credit');
    }

    /**
     * Get the net AR (outstanding balance)
     */
    public function getNetArAttribute()
    {
        return $this->invoice_amount - $this->settled_amount;
    }

    /**
     * Get the age of the invoice in days
     */
    public function getAgeAttribute()
    {
        return $this->invoice_date->diffInDays(now());
    }
}