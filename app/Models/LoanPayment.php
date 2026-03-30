<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id', 'payment_date', 'principal_paid', 'interest_paid',
        'total_payment', 'reference_no', 'payment_method', 'remarks', 'created_by',
    ];

    protected $casts = [
        'payment_date'    => 'date',
        'principal_paid'  => 'decimal:2',
        'interest_paid'   => 'decimal:2',
        'total_payment'   => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
