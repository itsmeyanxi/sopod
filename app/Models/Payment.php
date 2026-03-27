<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $table = 'payments';

    const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        // Exclude payments linked to hidden deliveries
        static::addGlobalScope('not_hidden_dr', function (Builder $builder) {
            $builder->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deliveries')
                    ->whereRaw('deliveries.dr_no COLLATE utf8mb4_unicode_ci = payments.dr_no COLLATE utf8mb4_unicode_ci')
                    ->where('deliveries.is_hidden', true);
            });
        });
    }

    /**
     * Include payments for hidden DRs (admin views).
     */
    public function scopeWithHiddenDr(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_hidden_dr');
    }

    protected $casts = [
        'collection_receipt_date' => 'date',
        'payment_posting_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'net' => 'decimal:2',
        'confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];
}