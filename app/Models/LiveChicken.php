<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChicken extends Model
{
    protected $fillable = [
        'grpo_no', 'date', 'po_no', 'reference_number', 'items_data', 'container_no', 'pallet_no', 'storage_name',
        'storage_reference_no', 'shipping_type',
        'supplier', 'items', 'brand', 'price', 'actual_qty',
        'delivery_date', 'docs_required_type', 'docs_required_file', 'docs_required_date',
        'docs_transmitted_type', 'docs_transmitted_file', 'docs_transmitted_date',
        'amount', 'status', 'delivery_week_no', 'created_by',
        'received_by_user_id', 'received_at', 'received_latitude', 'received_longitude', 'received_location',
        'grpo_approved_by_user_id', 'grpo_approved_at', 'grpo_approved_latitude', 'grpo_approved_longitude', 'grpo_approved_location',
    ];

    protected $casts = [
        'date'                  => 'date',
        'delivery_date'         => 'date',
        'docs_required_date'    => 'date',
        'docs_transmitted_date' => 'date',
        'price'                 => 'decimal:2',
        'actual_qty'            => 'decimal:2',
        'amount'                => 'decimal:2',
        'items_data'            => 'array',
        'received_at'           => 'datetime',
        'grpo_approved_at'      => 'datetime',
    ];

    const STATUSES = ['Paid', 'Ongoing', 'UN Office', 'No Documents'];

    const STATUS_COLORS = [
        'Paid'         => 'green',
        'Ongoing'      => 'blue',
        'UN Office'    => 'yellow',
        'No Documents' => 'red',
    ];

    public static function generateGrpoNo(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)
            ->whereNotNull('grpo_no')
            ->orderByDesc('id')->first();

        $seq = 1;
        if ($last && $last->grpo_no) {
            $parts = explode('-', $last->grpo_no);
            $seq = ((int) end($parts)) + 1;
        }

        return 'GRPO-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_no', 'po_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function grpoApprovedBy()
    {
        return $this->belongsTo(User::class, 'grpo_approved_by_user_id');
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
