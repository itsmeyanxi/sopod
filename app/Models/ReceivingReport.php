<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'rr_number',
        'sales_order_number',
        'customer_name',
        'customer_code',
        'tin_no',
        'branch',
        'sales_representative',
        'sales_executive',
        'po_number',
        'plate_no',
        'sales_invoice_no',
        'received_by',
        'delivery_batch',
        'delivery_type',
        'additional_instructions',
        'request_delivery_date',
        'status',
        'received_date',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'request_delivery_date' => 'date',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(ReceivingReportItem::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_number', 'sales_order_number');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'customer_code');
    }

    // ✅ NEW: Relationship to AR Adjustments (via delivery_batch as DR number)
    public function arAdjustments()
    {
        return $this->hasMany(ArAdjustment::class, 'dr_no', 'delivery_batch');
    }
}