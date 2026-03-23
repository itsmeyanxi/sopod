<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'txn_date',
        'type',
        'reference',
        'supplier_name',
        'description',
        'debit',
        'credit',
        'running_balance',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public static function addEntry(string $type, string $reference, string $supplierName, string $description, float $debit = 0, float $credit = 0): self
    {
        $lastEntry = self::orderBy('id', 'desc')->first();
        $previousBalance = $lastEntry ? (float) $lastEntry->running_balance : 0;
        $runningBalance = $previousBalance + $debit - $credit;

        return self::create([
            'txn_date' => now()->toDateString(),
            'type' => $type,
            'reference' => $reference,
            'supplier_name' => $supplierName,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $runningBalance,
        ]);
    }
}
