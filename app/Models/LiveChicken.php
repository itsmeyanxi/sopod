<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChicken extends Model
{
    protected $fillable = [
        'date', 'po_no', 'supplier', 'items', 'brand', 'price', 'actual_qty',
        'delivery_date', 'docs_required_type', 'docs_required_file', 'docs_required_date',
        'docs_transmitted_type', 'docs_transmitted_file', 'docs_transmitted_date',
        'amount', 'status', 'delivery_week_no', 'created_by',
    ];

    protected $casts = [
        'date'                  => 'date',
        'delivery_date'         => 'date',
        'docs_required_date'    => 'date',
        'docs_transmitted_date' => 'date',
        'price'                 => 'decimal:2',
        'actual_qty'            => 'decimal:2',
        'amount'                => 'decimal:2',
    ];

    const STATUSES = ['Paid', 'Ongoing', 'UN Office', 'No Documents'];

    const STATUS_COLORS = [
        'Paid'         => 'green',
        'Ongoing'      => 'blue',
        'UN Office'    => 'yellow',
        'No Documents' => 'red',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_no', 'po_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPoQty(): float
    {
        if (!$this->po_no) return 0;
        $po = PurchaseOrder::with('items')->where('po_no', $this->po_no)->first();
        return $po ? (float) $po->items->sum('qty') : 0;
    }

    public function isPoQtyMet(): bool
    {
        return (float) $this->actual_qty >= $this->getPoQty();
    }
}
