<?php

namespace App\Observers;

use App\Models\SalesOrder;
use App\Models\SalesOrderChange;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SalesOrderObserver
{
    protected $notificationService;
    private static $isNotifying = false;
    private static $processedUpdates = [];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the SalesOrder "created" event.
     */
    public function created(SalesOrder $salesOrder)
    {
        if (self::$isNotifying) {
            return;
        }

        try {
            self::$isNotifying = true;
            
            Log::info('🔔 SalesOrderObserver: created event', [
                'so_id' => $salesOrder->id,
                'so_number' => $salesOrder->sales_order_number
            ]);
            
            $this->notificationService->notifyNewSalesOrder($salesOrder);
            
        } catch (\Exception $e) {
            Log::error('❌ SalesOrderObserver: created notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            self::$isNotifying = false;
        }
    }

    /**
     * Handle the SalesOrder "updating" event.
     */
    public function updating(SalesOrder $salesOrder)
    {
        // Store original data in session temporarily
        $sessionKey = 'so_update_' . $salesOrder->id;
        
        session([
            $sessionKey . '_data' => $salesOrder->getOriginal(),
            $sessionKey . '_items' => $salesOrder->items->keyBy('id')->map(function($item) {
                return [
                    'item_id' => $item->item_id,
                    'item_code' => $item->item_code,
                    'item_description' => $item->item_description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit' => $item->unit,
                    'brand' => $item->brand,
                    'total_amount' => $item->total_amount,
                ];
            })->toArray(),
            $sessionKey . '_timestamp' => time() // Add timestamp for cleanup
        ]);
    }

    /**
     * Handle the SalesOrder "updated" event.
     */
    public function updated(SalesOrder $salesOrder)
    {
        // Prevent loop
        if (self::$isNotifying) {
            return;
        }

        // Prevent duplicate processing within same request
        $updateKey = $salesOrder->id . '_' . ($salesOrder->updated_at ? $salesOrder->updated_at->timestamp : time());
        if (isset(self::$processedUpdates[$updateKey])) {
            return;
        }
        self::$processedUpdates[$updateKey] = true;

        $sessionKey = 'so_update_' . $salesOrder->id;
        $original = session($sessionKey . '_data');
        
        if (!$original) {
            return;
        }

        try {
            self::$isNotifying = true;
            $userId = Auth::id();
            $notificationSent = false;

            // CHECK 1: If Sales Order was closed
            if (isset($original['is_closed']) && 
                $original['is_closed'] != $salesOrder->is_closed && 
                $salesOrder->is_closed == 1) {
                
                Log::info('🔔 SalesOrderObserver: Sales Order closed', [
                    'so_id' => $salesOrder->id
                ]);
                
                $this->notificationService->notifySalesOrderClosed($salesOrder);
                $notificationSent = true;
                
                SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId) {
                    SalesOrderChange::create([
                        'sales_order_id' => $salesOrder->id,
                        'user_id' => $userId,
                        'field_changed' => 'is_closed',
                        'old_value' => 'Open',
                        'new_value' => 'Closed',
                        'change_type' => 'update',
                    ]);
                });
                
                $this->cleanupSession($sessionKey);
                return;
            }

            // CHECK 2: If status changed
            if (isset($original['status']) && 
                $original['status'] !== $salesOrder->status) {
                
                $oldStatus = $original['status'];
                $newStatus = $salesOrder->status;
                
                Log::info('🔔 SalesOrderObserver: Status changed', [
                    'so_id' => $salesOrder->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
                
                $this->notificationService->notifySalesOrderStatusChange(
                    $salesOrder, 
                    $oldStatus, 
                    $newStatus
                );
                $notificationSent = true;
            }

            // CHECK 3: If other meaningful fields changed
            if (!$notificationSent) {
                $changedFields = array_keys($salesOrder->getChanges());
                $onlySystemFields = empty(array_diff($changedFields, ['status', 'is_closed', 'updated_at']));
                
                if ($salesOrder->wasChanged() && !$onlySystemFields) {
                    Log::info('🔔 SalesOrderObserver: General update', [
                        'so_id' => $salesOrder->id,
                        'changed_fields' => $changedFields
                    ]);
                    
                    $this->notificationService->notifySalesOrderUpdated($salesOrder);
                }
            }

            // Track changes to main SO fields
            $this->trackFieldChanges($salesOrder, $original, $userId);
            
            // Track item changes
            $this->trackItemChanges($salesOrder, $userId);
            
        } catch (\Exception $e) {
            Log::error('❌ SalesOrderObserver: update processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        } finally {
            self::$isNotifying = false;
            $this->cleanupSession($sessionKey);
        }
    }

    /**
     * Clean up session data
     */
    private function cleanupSession($sessionKey)
    {
        session()->forget([
            $sessionKey . '_data', 
            $sessionKey . '_items',
            $sessionKey . '_timestamp'
        ]);
    }

    /**
     * Track changes to main fields
     */
    private function trackFieldChanges($salesOrder, $original, $userId)
    {
        $trackableFields = [
            'customer_id',
            'po_number',
            'request_delivery_date',
            'shipping_address',
            'sales_rep',
            'status',
            'total_amount',
            'additional_instructions',
        ];

        foreach ($trackableFields as $field) {
            $oldValue = $original[$field] ?? null;
            $newValue = $salesOrder->$field ?? null;

            if ($oldValue == $newValue) {
                continue;
            }

            $oldDisplay = $this->formatValue($field, $oldValue, $salesOrder);
            $newDisplay = $this->formatValue($field, $newValue, $salesOrder);

            SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $field, $oldDisplay, $newDisplay) {
                SalesOrderChange::create([
                    'sales_order_id' => $salesOrder->id,
                    'user_id' => $userId,
                    'field_changed' => $field,
                    'old_value' => $oldDisplay,
                    'new_value' => $newDisplay,
                    'change_type' => 'update',
                ]);
            });
        }
    }

    /**
     * Track changes to sales order items
     */
    private function trackItemChanges(SalesOrder $salesOrder, $userId)
    {
        $sessionKey = 'so_update_' . $salesOrder->id;
        $originalItems = collect(session($sessionKey . '_items', []));
        
        if ($originalItems->isEmpty()) {
            return;
        }

        $currentItems = $salesOrder->fresh()->items;

        $originalMap = [];
        foreach ($originalItems as $item) {
            $key = $item['item_code'] . '_' . $item['quantity'] . '_' . $item['unit_price'];
            $originalMap[$key] = $item;
        }
        
        $currentMap = [];
        foreach ($currentItems as $item) {
            $key = $item->item_code . '_' . $item->quantity . '_' . $item->unit_price;
            $currentMap[$key] = $item;
        }

        $originalByCode = $originalItems->groupBy('item_code');
        $currentByCode = $currentItems->groupBy('item_code');
        
        foreach ($currentByCode as $itemCode => $currentItemGroup) {
            if (isset($originalByCode[$itemCode])) {
                $originalItemGroup = $originalByCode[$itemCode];
                
                if ($originalItemGroup->count() == 1 && $currentItemGroup->count() == 1) {
                    $originalItem = $originalItemGroup->first();
                    $currentItem = $currentItemGroup->first();
                    
                    // Track quantity change
                    if ($originalItem['quantity'] != $currentItem->quantity) {
                        SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $originalItem, $currentItem) {
                            SalesOrderChange::create([
                                'sales_order_id' => $salesOrder->id,
                                'user_id' => $userId,
                                'field_changed' => 'quantity',
                                'old_value' => number_format($originalItem['quantity'], 2),
                                'new_value' => number_format($currentItem->quantity, 2),
                                'change_type' => 'update',
                            ]);
                        });
                    }
                    
                    // Track price change
                    if ($originalItem['unit_price'] != $currentItem->unit_price) {
                        SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $originalItem, $currentItem) {
                            SalesOrderChange::create([
                                'sales_order_id' => $salesOrder->id,
                                'user_id' => $userId,
                                'field_changed' => 'unit_price',
                                'old_value' => '₱' . number_format($originalItem['unit_price'], 2),
                                'new_value' => '₱' . number_format($currentItem->unit_price, 2),
                                'change_type' => 'update',
                            ]);
                        });
                    }
                    
                    // Track product name change
                    if ($originalItem['item_description'] != $currentItem->item_description) {
                        SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $originalItem, $currentItem) {
                            SalesOrderChange::create([
                                'sales_order_id' => $salesOrder->id,
                                'user_id' => $userId,
                                'field_changed' => 'product_name',
                                'old_value' => $originalItem['item_description'],
                                'new_value' => $currentItem->item_description,
                                'change_type' => 'update',
                            ]);
                        });
                    }
                }
            }
        }

        // Track added items
        foreach ($currentMap as $key => $currentItem) {
            if (!isset($originalMap[$key])) {
                $itemCodeExisted = $originalItems->where('item_code', $currentItem->item_code)->isNotEmpty();
                
                if (!$itemCodeExisted) {
                    SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $currentItem) {
                        SalesOrderChange::create([
                            'sales_order_id' => $salesOrder->id,
                            'user_id' => $userId,
                            'field_changed' => 'line_item',
                            'old_value' => null,
                            'new_value' => json_encode([
                                'item_code' => $currentItem->item_code,
                                'product_name' => $currentItem->item_description,
                                'quantity' => $currentItem->quantity,
                                'unit_price' => $currentItem->unit_price,
                                'unit' => $currentItem->unit,
                                'brand' => $currentItem->brand,
                            ]),
                            'change_type' => 'create',
                        ]);
                    });
                }
            }
        }

        // Track removed items
        foreach ($originalMap as $key => $originalItem) {
            if (!isset($currentMap[$key])) {
                $itemCodeStillExists = $currentItems->where('item_code', $originalItem['item_code'])->isNotEmpty();
                
                if (!$itemCodeStillExists) {
                    SalesOrderChange::withoutEvents(function() use ($salesOrder, $userId, $originalItem) {
                        SalesOrderChange::create([
                            'sales_order_id' => $salesOrder->id,
                            'user_id' => $userId,
                            'field_changed' => 'line_item',
                            'old_value' => json_encode([
                                'item_code' => $originalItem['item_code'],
                                'product_name' => $originalItem['item_description'],
                                'quantity' => $originalItem['quantity'],
                                'unit_price' => $originalItem['unit_price'],
                                'unit' => $originalItem['unit'],
                                'brand' => $originalItem['brand'],
                            ]),
                            'new_value' => null,
                            'change_type' => 'delete',
                        ]);
                    });
                }
            }
        }
    }

    /**
     * Format values for display
     */
    private function formatValue($field, $value, $salesOrder)
    {
        if ($value === null) {
            return 'None';
        }

        switch ($field) {
            case 'customer_id':
                $customer = \App\Models\Customer::find($value);
                return $customer ? $customer->customer_name : 'Unknown';
            
            case 'total_amount':
                return '₱' . number_format($value, 2);
            
            case 'request_delivery_date':
                return date('M d, Y', strtotime($value));
            
            default:
                return $value;
        }
    }
}