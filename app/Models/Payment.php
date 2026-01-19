<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    
    const UPDATED_AT = null;
    
    protected $guarded = [];

    protected $casts = [
        'collection_receipt_date' => 'date',
        'payment_posting_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
    ];
}