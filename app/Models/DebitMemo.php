<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitMemo extends Model
{
    use HasFactory;

    protected $fillable = [
        'dm_no',
        'supplier_id',
        'supplier_name',
        'invoice_id',
        'invoice_no',
        'memo_date',
        'amount',
        'reason',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'memo_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(AccountsPayableInvoice::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
