<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deliveries extends Model
{
    protected $table = 'deliveries';

    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [ 
        'sales_order_number',
        'customer_code',
        'customer_name',
        'sales_executive',
        'branch',
        'sales_representative',
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

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'request_delivery_date' => 'date',
        'dr_weight' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(DeliveryItem::class, 'delivery_id', 'id');
    }

    /**
     * Relationship with User (approver)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /**
     * Relationship with User (preparer)
     */
    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by', 'id');
    }

    /**
     * Relationship with SalesOrder
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_number', 'sales_order_number');
    }
    

    /**
     * Get customer name with fallback logic
     */
    public function getCustomerNameAttribute($value)
    {
        // First, use the stored value if it exists
        if ($value) {
            return $value;
        }

        // Try salesOrder -> customer
        if ($this->salesOrder && $this->salesOrder->customer) {
            return $this->salesOrder->customer->customer_name;
        }
        
        // Try salesOrder -> customer_name field
        if ($this->salesOrder && $this->salesOrder->customer_name) {
            return $this->salesOrder->customer_name;
        }
        
        // Try direct lookup by customer_code
        if ($this->customer_code) {
            $customer = \App\Models\Customer::where('customer_code', $this->customer_code)->first();
            return $customer ? $customer->customer_name : 'N/A';
        }
        
        return 'N/A';
    }
}