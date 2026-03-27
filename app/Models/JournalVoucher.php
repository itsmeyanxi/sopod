<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalVoucher extends Model
{
    protected $fillable = [
        'jv_number', 'jv_date', 'transaction_type', 'description',
        'reference_no', 'department', 'total_debit', 'total_credit',
        'status', 'remarks', 'prepared_by', 'checked_by', 'approved_by',
        'posted_at', 'attachment_path', 'attachment_name', 'created_by',
    ];

    protected $casts = [
        'jv_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalVoucherLine::class)->orderBy('sort_order');
    }

    public function getFormattedTypeAttribute()
    {
        return match($this->transaction_type) {
            'bank_interest' => 'Bank Interest',
            'bank_charges' => 'Bank Charges',
            'reclassification' => 'Reclassification',
            'adjustment' => 'Adjustment',
            'correction' => 'Correction',
            'accrual' => 'Accrual',
            'reversal' => 'Reversal',
            'depreciation' => 'Depreciation',
            'tax_adjustment' => 'Tax Adjustment',
            'intercompany' => 'Intercompany',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->transaction_type)),
        };
    }

    public function getIsBalancedAttribute()
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'Posted');
    }
}
