<?php

namespace App\Observers;

use App\Models\Deliveries;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class DeliveryObserver
{
    protected $notificationService;
    private static $isNotifying = false;
    private static $processedUpdates = [];

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Deliveries "created" event.
     */
    public function created(Deliveries $delivery)
    {
        if (self::$isNotifying) {
            return;
        }

        try {
            self::$isNotifying = true;
            
            Log::info('🔔 DeliveryObserver: created event', [
                'delivery_id' => $delivery->id,
                'dr_no' => $delivery->dr_no
            ]);
            
            $this->notificationService->notifyNewDelivery($delivery);
            
        } catch (\Exception $e) {
            Log::error('❌ DeliveryObserver: created notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'delivery_id' => $delivery->id ?? 'unknown'
            ]);
        } finally {
            self::$isNotifying = false;
        }
    }

    /**
     * Handle the Deliveries "updated" event.
     */
    public function updated(Deliveries $delivery)
    {
        // Prevent loop
        if (self::$isNotifying) {
            return;
        }

        // Prevent duplicate processing within same request
        $updateKey = $delivery->id . '_' . ($delivery->updated_at ? $delivery->updated_at->timestamp : time());
        if (isset(self::$processedUpdates[$updateKey])) {
            return;
        }
        self::$processedUpdates[$updateKey] = true;

        try {
            // Check if approval_status actually changed
            if (!$delivery->wasChanged('approval_status')) {
                return;
            }

            self::$isNotifying = true;
            
            $newStatus = $delivery->approval_status;
            $oldStatus = $delivery->getOriginal('approval_status');
            
            // Skip if no meaningful change
            if ($oldStatus === $newStatus) {
                return;
            }
            
            Log::info('🔔 DeliveryObserver: approval status changed', [
                'delivery_id' => $delivery->id,
                'dr_no' => $delivery->dr_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            
            if ($newStatus === 'Approved') {
                $this->notificationService->notifyDeliveryStatusChange($delivery, 'approved');
            } elseif ($newStatus === 'Rejected') {
                $this->notificationService->notifyDeliveryStatusChange($delivery, 'rejected');
            }
            
        } catch (\Exception $e) {
            Log::error('❌ DeliveryObserver: update notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'delivery_id' => $delivery->id ?? 'unknown',
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Don't throw - let the update succeed even if notification fails
            
        } finally {
            self::$isNotifying = false;
        }
    }
}