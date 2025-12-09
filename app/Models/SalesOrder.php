<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;        // ✅ For logging
use Illuminate\Support\Facades\DB;         // ✅ For DB::raw()
use App\Models\DeliveryItem;               // ✅ For DeliveryItem queries
use App\Models\Activity;                   // ✅ For Activity::create()

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
        'is_closed',
        'shipping_address'
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

    /**
     * Check if all items are delivered and close SO if needed
     * Returns true if SO was closed, false otherwise
     */
    public function checkAndClose()
    {
        try {
            Log::info('🔄 Checking SO closure', [
                'so_number' => $this->sales_order_number,
                'current_is_closed' => $this->is_closed
            ]);
            
            // Get all ACTIVE SO items
            $soItems = $this->items()->where('batch_status', 'Active')->get();
            
            if ($soItems->isEmpty()) {
                Log::warning('⚠️ No active items', ['so_number' => $this->sales_order_number]);
                return false;
            }
            
            // Get delivered quantities
            $deliveredSums = DeliveryItem::whereHas('delivery', function($q) {
                    $q->where('sales_order_number', $this->sales_order_number)
                      ->where('status', 'Delivered');
                })
                ->select('item_code', DB::raw('SUM(quantity) as total_delivered'))
                ->groupBy('item_code')
                ->get()
                ->keyBy('item_code');
            
            $allFullyDelivered = true;
            $debugInfo = [];
            
            foreach ($soItems as $soItem) {
                $soQty = floatval($soItem->quantity);
                $deliveredQty = floatval($deliveredSums->get($soItem->item_code)?->total_delivered ?? 0);
                
                $debugInfo[] = [
                    'item_code' => $soItem->item_code,
                    'so_qty' => $soQty,
                    'delivered_qty' => $deliveredQty,
                    'remaining' => $soQty - $deliveredQty,
                ];
                
                if ($deliveredQty < $soQty) {
                    $allFullyDelivered = false;
                }
            }
            
            Log::info('📊 SO Analysis', [
                'so_number' => $this->sales_order_number,
                'all_fully_delivered' => $allFullyDelivered,
                'items' => $debugInfo
            ]);
            
            // Close if all delivered
            if ($allFullyDelivered && !$this->is_closed) {
                $this->update(['is_closed' => 1]);
                
                Log::info('✅ SO CLOSED', ['so_number' => $this->sales_order_number]);
                
                Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Closed',
                    'item' => $this->sales_order_number,
                    'target' => $this->customer->customer_name ?? 'N/A',
                    'type' => 'Sales Order',
                    'message' => "Sales Order automatically closed - all items delivered",
                ]);
                
                return true;
            }
            
            // Reopen if not all delivered
            if (!$allFullyDelivered && $this->is_closed) {
                $this->update(['is_closed' => 0]);
                
                Log::info('🔓 SO REOPENED', ['so_number' => $this->sales_order_number]);
                
                Activity::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'Reopened',
                    'item' => $this->sales_order_number,
                    'target' => $this->customer->customer_name ?? 'N/A',
                    'type' => 'Sales Order',
                    'message' => "Sales Order reopened - items still have remaining quantities",
                ]);
                
                return true;
            }
            
            Log::info('ℹ️ No change needed', ['so_number' => $this->sales_order_number]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('❌ SO closure check failed', [
                'so_number' => $this->sales_order_number,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()  // ✅ Added for better debugging
            ]);
            return false;
        }
    }
}