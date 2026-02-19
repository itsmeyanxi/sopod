<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReceivingReportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_receiving_report_id',
        'item_no',
        'item_code',
        'item_description',
        'brand',
        'no_of_boxes',
        'net_weight',
        'pd',
        'expiry_date',
        'pallet_no',
        'remarks',
    ];

    protected $casts = [
        'no_of_boxes' => 'integer',
        'net_weight' => 'decimal:2',
        'pd' => 'date',
        'expiry_date' => 'date',
    ];

    public function supplierReceivingReport()
    {
        return $this->belongsTo(SupplierReceivingReport::class);
    }
}
