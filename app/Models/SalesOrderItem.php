<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\SalesOrderChange;
use App\Events\SalesOrderChanged;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'item_id',
        'item_code',
        'item_description',
        'brand',
        'item_category',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'batch_status',
        'delivery_batch',           
        'request_delivery_date',
        'note',  
    ];

    protected $casts = [
        'request_delivery_date' => 'date', 
    ];

    /**
     * Boot method - Track changes automatically
     */
    protected static function booted()
    {
        // Track item changes when updating
        static::updating(function ($item) {
            $tracked_fields = [
                'quantity' => 'Quantity',
                'unit_price' => 'Unit Price',
                'total_amount' => 'Total Amount',
                'item_description' => 'Item Description',
                'batch_status' => 'Batch Status',
                'delivery_batch' => 'Delivery Batch',
                'request_delivery_date' => 'Request Delivery Date',
            ];

            foreach ($tracked_fields as $field => $label) {
                if ($item->isDirty($field)) {
                    $oldValue = $item->getOriginal($field);
                    $newValue = $item->$field;
                    
                    // Get item identifier for display
                    $itemIdentifier = $item->item_description ?? $item->item_code ?? 'Unknown Item';
                    
                    // Format values for better display
                    if (in_array($field, ['unit_price', 'total_amount'])) {
                        $oldValue = '₱' . number_format($oldValue, 2);
                        $newValue = '₱' . number_format($newValue, 2);
                    } elseif ($field === 'quantity') {
                        $unit = $item->unit ?? 'kg';
                        $oldValue = number_format($oldValue, 2) . ' ' . $unit;
                        $newValue = number_format($newValue, 2) . ' ' . $unit;
                    } elseif ($field === 'request_delivery_date') {
                        $oldValue = $oldValue ? date('M d, Y', strtotime($oldValue)) : 'Not set';
                        $newValue = $newValue ? date('M d, Y', strtotime($newValue)) : 'Not set';
                    }
                    
                    // Create change record
                    $change = SalesOrderChange::create([
                        'sales_order_id' => $item->sales_order_id,
                        'user_id' => auth()->id(),
                        'field_changed' => "line_item_{$field}",
                        'old_value' => "[{$itemIdentifier}] {$label}: {$oldValue}",
                        'new_value' => "[{$itemIdentifier}] {$label}: {$newValue}",
                        'change_type' => 'update',
                    ]);

                    // Log the change
                    Log::info('📝 Line Item Change Tracked', [
                        'so_id' => $item->sales_order_id,
                        'item' => $itemIdentifier,
                        'field' => $label,
                        'old' => $oldValue,
                        'new' => $newValue,
                        'user' => auth()->user()->name ?? 'System'
                    ]);

                    // Trigger event for notifications
                    if ($item->salesOrder) {
                        event(new SalesOrderChanged($item->salesOrder, "line_item_{$field}", $change));
                    }
                }
            }
        });

        // Track when item is added
        static::created(function ($item) {
            $itemIdentifier = $item->item_description ?? $item->item_code ?? 'Unknown Item';
            $unit = $item->unit ?? 'kg';
            $qty = number_format($item->quantity, 2) . ' ' . $unit;
            $price = '₱' . number_format($item->unit_price ?? 0, 2);
            
            $change = SalesOrderChange::create([
                'sales_order_id' => $item->sales_order_id,
                'user_id' => auth()->id(),
                'field_changed' => 'line_item_added',
                'old_value' => null,
                'new_value' => "Added Item: {$itemIdentifier} | Qty: {$qty} | Price: {$price}",
                'change_type' => 'create',
            ]);

            Log::info('✨ Line Item Added', [
                'so_id' => $item->sales_order_id,
                'item' => $itemIdentifier,
                'quantity' => $qty,
                'user' => auth()->user()->name ?? 'System'
            ]);

            if ($item->salesOrder) {
                event(new SalesOrderChanged($item->salesOrder, 'line_item_added', $change));
            }
        });

        // Track when item is deleted
        static::deleting(function ($item) {
            $itemIdentifier = $item->item_description ?? $item->item_code ?? 'Unknown Item';
            $unit = $item->unit ?? 'kg';
            $qty = number_format($item->quantity, 2) . ' ' . $unit;
            $price = '₱' . number_format($item->unit_price ?? 0, 2);
            
            $change = SalesOrderChange::create([
                'sales_order_id' => $item->sales_order_id,
                'user_id' => auth()->id(),
                'field_changed' => 'line_item_removed',
                'old_value' => "Removed Item: {$itemIdentifier} | Qty: {$qty} | Price: {$price}",
                'new_value' => null,
                'change_type' => 'delete',
            ]);

            Log::info('🗑️ Line Item Removed', [
                'so_id' => $item->sales_order_id,
                'item' => $itemIdentifier,
                'quantity' => $qty,
                'user' => auth()->user()->name ?? 'System'
            ]);

            if ($item->salesOrder) {
                event(new SalesOrderChanged($item->salesOrder, 'line_item_removed', $change));
            }
        });
    }

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    
    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class, 'sales_order_item_id');
    }
}