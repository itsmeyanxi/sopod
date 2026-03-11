<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ARAdjustment extends Model
{
    protected $table = 'ar_adjustments';

    protected $fillable = [
        'customer_code',
        'customer_name',
        'branch',
        'reference_number',
        'transaction_type',
        'transaction_date',
        'amount',
        'is_decrease',
        'invoice_number',
        'dr_no',
        'gl_account',
        'signed_by',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'is_decrease' => 'boolean',
    ];

    /**
     * Get the customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    /**
     * Get the AR aging record if linked
     */
    public function arAging()
    {
        return $this->belongsTo(ArAging::class, 'invoice_number', 'invoice_no');
    }

    /**
     * Get the delivery record if linked
     */
    public function delivery()
    {
        return $this->belongsTo(Deliveries::class, 'dr_no', 'dr_no');
    }

    /**
     * Get the receiving report if linked (via DR number)
     */
    public function receivingReport()
    {
        return $this->belongsTo(ReceivingReport::class, 'dr_no', 'delivery_batch');
    }

    /**
     * Get the customer record
     */
    public function customerRecord()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    /**
     * Get the user who created this adjustment
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get absolute amount
     */
    // public function getAbsoluteAmountAttribute()
    // {
    //     try {
    //         return abs($this->amount ?? 0);
    //     } catch (\Exception $e) {
    //         return 0;
    //     }
    // }

    public function getFormattedTypeAttribute()
{
    return match($this->transaction_type) {
        'atd' => 'ATD (Authority to Debit)',
        'offset' => 'Offset',
        'credit_memo' => 'Credit Memo',
        'debit_memo' => 'Debit Memo',
        'adjustment' => 'Adjustment',
        'write_off' => 'Write-off',
        default => ucfirst(str_replace('_', ' ', $this->transaction_type))
    };
}

public function getAbsoluteAmountAttribute()
{
    return abs($this->amount);
}
}