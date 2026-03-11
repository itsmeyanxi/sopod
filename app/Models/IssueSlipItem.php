<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueSlipItem extends Model
{
    protected $fillable = [
        'issue_slip_id',
        'sales_order_item_id',
        'item_code',
        'item_description',
        'brand',
        'item_category',
        'origin',
        'so_quantity',
        'number_of_boxes',
        'net_weight',
        'actual_weight',
    ];

    public function issueSlip()
    {
        return $this->belongsTo(IssueSlip::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}
