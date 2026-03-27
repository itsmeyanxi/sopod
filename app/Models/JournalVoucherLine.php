<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalVoucherLine extends Model
{
    protected $fillable = [
        'journal_voucher_id', 'account_code', 'account_name',
        'line_description', 'cost_center', 'debit', 'credit', 'sort_order',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class);
    }
}
