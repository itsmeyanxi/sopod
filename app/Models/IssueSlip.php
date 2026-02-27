<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueSlip extends Model
{
    protected $fillable = [
        'issue_slip_number',
        'date',
        'origin',
        'sales_order_id',
        'sales_order_number',
        'customer_name',
        'customer_id',
        'destination',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(IssueSlipItem::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
