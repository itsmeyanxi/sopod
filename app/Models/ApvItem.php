<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApvItem extends Model
{
    protected $fillable = [
        'apv_id', 'particulars', 'item_code', 'department', 'division',
        'vat', 'tax_code', 'account_code', 'account_name', 'gross_amount',
    ];

    protected $casts = [
        'vat'          => 'boolean',
        'gross_amount' => 'decimal:2',
    ];

    public function apv()
    {
        return $this->belongsTo(AccountsPayableInvoice::class, 'apv_id');
    }
}
