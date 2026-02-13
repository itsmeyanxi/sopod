<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'item_no',
        'qty',
        'uom',
        'description',
        'unit_price',
        'amount',
        'remarks',
    ];

    /**
     * Relationship with PurchaseRequest
     */
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id', 'id');
    }
}
