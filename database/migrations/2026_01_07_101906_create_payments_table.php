<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'customer_name',
        'collection_receipt_number',
        'collection_receipt_date',
        'payment_posting_date',
        'amount',
        'tax',
        'payment_option',
        'payment_notes',
        'created_by',
    ];

    protected $casts = [
        'collection_receipt_date' => 'date',
        'payment_posting_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    /**
     * Get the customer associated with this payment
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }
}