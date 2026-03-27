<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryBankAccount extends Model
{
    protected $fillable = [
        'account_number', 'bank_name', 'short_name', 'currency',
        'account_type', 'cash_balance', 'balance_as_of',
        'gl_account_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'cash_balance' => 'decimal:2',
        'balance_as_of' => 'date',
        'is_active' => 'boolean',
    ];

    public function glAccount()
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(TreasuryBankTransaction::class, 'bank_account_id');
    }

    public function scopePeso($query)
    {
        return $query->where('currency', 'PHP');
    }

    public function scopeDollar($query)
    {
        return $query->where('currency', 'USD');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
