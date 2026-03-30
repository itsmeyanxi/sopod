<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'loan_no', 'loan_date', 'lender_name', 'lender_address',
        'loan_type', 'purpose', 'principal_amount', 'interest_rate',
        'loan_term_months', 'disbursement_date', 'maturity_date',
        'payment_frequency', 'gl_loan_account', 'gl_cash_account',
        'gl_interest_expense', 'outstanding_principal',
        'total_interest_paid', 'total_principal_paid',
        'status', 'terms_conditions', 'remarks',
        'prepared_by', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'loan_date'           => 'date',
        'disbursement_date'   => 'date',
        'maturity_date'       => 'date',
        'principal_amount'    => 'decimal:2',
        'interest_rate'       => 'decimal:4',
        'outstanding_principal' => 'decimal:2',
        'total_interest_paid' => 'decimal:2',
        'total_principal_paid' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class)->orderBy('payment_date');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /** Monthly payment using standard amortisation formula */
    public function getMonthlyPaymentAttribute(): float
    {
        if ($this->loan_term_months <= 0) return 0;
        $r = ($this->interest_rate / 100) / 12;
        if ($r == 0) {
            return round((float) $this->principal_amount / $this->loan_term_months, 2);
        }
        $n = $this->loan_term_months;
        return round((float) $this->principal_amount * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1), 2);
    }

    public function getRemainingMonthsAttribute(): int
    {
        if (!$this->maturity_date) return 0;
        return max(0, (int) now()->diffInMonths($this->maturity_date, false));
    }

    public function getPayoffPercentageAttribute(): float
    {
        if ((float) $this->principal_amount == 0) return 0;
        return round(((float) $this->total_principal_paid / (float) $this->principal_amount) * 100, 1);
    }
}
