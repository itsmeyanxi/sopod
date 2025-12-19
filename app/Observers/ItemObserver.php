<?php

namespace App\Observers;

use App\Models\Item;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ItemObserver
{
    protected $notificationService;
    private static $isNotifying = false;
    private static $processedUpdates = [];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item)
    {
        if (self::$isNotifying) {
            return;
        }

        try {
            self::$isNotifying = true;
            
            Log::info('🔔 ItemObserver: created event', [
                'item_id' => $item->id,
                'item_code' => $item->item_code
            ]);
            
            $this->notificationService->notifyNewItem($item);
            
        } catch (\Exception $e) {
            Log::error('❌ ItemObserver: created notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'item_id' => $item->id ?? 'unknown'
            ]);
        } finally {
            self::$isNotifying = false;
        }
    }

    /**
     * Handle the Item "updated" event.
     */
    public function updated(Item $item)
    {
        // Prevent loop
        if (self::$isNotifying) {
            return;
        }

        // Prevent duplicate processing within same request
        $updateKey = $item->id . '_' . ($item->updated_at ? $item->updated_at->timestamp : time());
        if (isset(self::$processedUpdates[$updateKey])) {
            return;
        }
        self::$processedUpdates[$updateKey] = true;

        try {
            // Check if status actually changed
            if (!$item->wasChanged('status')) {
                return;
            }

            self::$isNotifying = true;
            
            $newStatus = $item->status;
            $oldStatus = $item->getOriginal('status');
            
            // Skip if no meaningful change
            if ($oldStatus === $newStatus) {
                return;
            }
            
            Log::info('🔔 ItemObserver: status changed', [
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            
            if ($newStatus === 'Approved') {
                $this->notificationService->notifyItemStatusChange($item, 'approved');
            } elseif ($newStatus === 'Rejected') {
                $this->notificationService->notifyItemStatusChange($item, 'rejected');
            }
            
        } catch (\Exception $e) {
            Log::error('❌ ItemObserver: update notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'item_id' => $item->id ?? 'unknown',
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Don't throw - let the update succeed even if notification fails
            
        } finally {
            self::$isNotifying = false;
        }
    }
}