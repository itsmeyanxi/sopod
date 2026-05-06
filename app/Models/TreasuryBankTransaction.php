<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TreasuryBankTransaction extends Model
{
    protected static function booted(): void
    {
        // Exclude transactions linked to hidden deliveries (via payments → deliveries)
        static::addGlobalScope('not_hidden_dr', function (Builder $builder) {
            $builder->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('payments')
                    ->join('deliveries', function ($join) {
                        $join->on(DB::raw('deliveries.dr_no COLLATE utf8mb4_unicode_ci'), '=', DB::raw('payments.dr_no COLLATE utf8mb4_unicode_ci'));
                    })
                    ->whereRaw('payments.collection_receipt_number COLLATE utf8mb4_unicode_ci = treasury_bank_transactions.reference COLLATE utf8mb4_unicode_ci')
                    ->where('deliveries.is_hidden', true);
            });
        });
    }

    public function scopeWithHiddenDr(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_hidden_dr');
    }

    protected $fillable = [
        'bank_account_id', 'txn_date', 'type', 'reference', 'check_number',
        'currency', 'exchange_rate', 'amount_php',
        'description', 'payee_or_source', 'debit', 'credit',
        'running_balance', 'logged_by', 'dr_account', 'cr_account',
    ];

    protected $casts = [
        'txn_date'       => 'date',
        'debit'          => 'decimal:2',
        'credit'         => 'decimal:2',
        'running_balance'=> 'decimal:2',
        'exchange_rate'  => 'decimal:4',
        'amount_php'     => 'decimal:2',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(TreasuryBankAccount::class, 'bank_account_id');
    }
}
