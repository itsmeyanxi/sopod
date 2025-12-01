<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deliveries extends Model
{
    protected $table = 'deliveries';

    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

   // app/Models/Deliveries.php
        protected $fillable = [
        'sales_order_number',
        'delivery_batch', 
        'delivery_type',
        'customer_code',
        'customer_name',
        'sales_executive',
        'branch',
        'sales_rep',
        'po_number',
        'request_delivery_date',
        'plate_no',
        'sales_invoice_no',
        'dr_no',
        'status',
        'item_code',         
        'item_description',  
        'quantity',           
        'uom',                
        'unit_price',        
        'total_amount',      
        'approved_by',
        'additional_instructions', 
        'attachment',
        'dr_weight',
    ];

    // ✅ NEW: Scope for filtering by batch
    public function scopeByBatch($query, $batch)
    {
        return $query->where('delivery_batch', $batch);
    }

    // ✅ NEW: Get delivery batch display name
    public function getBatchNameAttribute()
    {
        if (!$this->delivery_batch) return 'Single Delivery';
        
        // Extract date from batch: SO-0001-20250115 -> 2025-01-15
        $parts = explode('-', $this->delivery_batch);
        if (count($parts) >= 3) {
            $dateStr = end($parts); // 20250115
            try {
                return 'Batch ' . \Carbon\Carbon::parse($dateStr)->format('M d, Y');
            } catch (\Exception $e) {
                return $this->delivery_batch;
            }
        }
        
        return $this->delivery_batch;
    }

    // ✅ NEW: Check if SO has multiple batches
    public static function hasMultipleBatches($soNumber)
    {
        return self::where('sales_order_number', $soNumber)
            ->distinct('delivery_batch')
            ->count('delivery_batch') > 1;
    }

    // ✅ NEW: Get all batches for an SO
    public static function getBatchesForSO($soNumber)
    {
        return self::where('sales_order_number', $soNumber)
            ->select('delivery_batch', 'request_delivery_date')
            ->distinct()
            ->orderBy('request_delivery_date')
            ->get();
    }

        public function items()
    {
        return $this->hasMany(\App\Models\DeliveryItem::class, 'delivery_id');
    }

        public function salesOrder()
    {
        return $this->belongsTo(\App\Models\SalesOrder::class, 'sales_order_id');
    }
}