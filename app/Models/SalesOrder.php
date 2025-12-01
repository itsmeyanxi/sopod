<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_number',
        'customer_id',
        'prepared_by',
        'approved_by',
        'status',
        'total_amount',
        'additional_instructions',
        'request_delivery_date',
        'po_number',
        'branch',
        'sales_rep',
        'sales_executive',
        'customer_name',
        'item_description', 
        'item_code',        
        'brand',            
        'item_category',     
        'unit',              
    ];

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Relationship with SalesOrderItems
     */
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id');
    }

    /**
     * Relationship with User (approver)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship with User (preparer)
     */
    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function deliveries()
    {
        return $this->hasOne(Deliveries::class, 'sales_order_number', 'sales_order_number');
    }

}