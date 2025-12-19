<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Mail\SalesOrderCreated;
use App\Mail\SalesOrderStatusChanged;
use App\Mail\SalesOrderUpdated;
use App\Mail\SalesOrderClosed;
use App\Mail\DeliveryCreated;
use App\Mail\DeliveryStatusChanged;
use App\Mail\DeliveryUpdated;
use App\Mail\ItemCreated;
use App\Mail\ItemStatusChanged;

class NotificationService
{
    // ✅ GMAIL LIMITS: 500/day, ~100/hour
    private $maxEmailsPerHour = 80;   // Safe hourly limit
    private $maxEmailsPerDay = 450;   // Safe daily limit (buffer for manual emails)

    /**
     * ✅ HELPER: Check if this exact notification was recently sent
     */
    private function isDuplicateNotification($key)
    {
        // Use Cache instead of static array (survives across requests)
        if (Cache::has('email_sent_' . $key)) {
            Log::warning('⚠️ Duplicate email blocked', ['key' => $key]);
            return true;
        }
        
        // Mark as sent for 30 seconds
        Cache::put('email_sent_' . $key, true, 30);
        return false;
    }

    /**
     * ✅ CHECK: Gmail rate limits
     */
    private function canSendEmail()
    {
        $hourKey = 'gmail_count_hour_' . now()->format('Y-m-d-H');
        $dayKey = 'gmail_count_day_' . now()->format('Y-m-d');
        
        $hourCount = Cache::get($hourKey, 0);
        $dayCount = Cache::get($dayKey, 0);
        
        if ($hourCount >= $this->maxEmailsPerHour) {
            Log::error('🚫 GMAIL HOURLY LIMIT REACHED', [
                'sent' => $hourCount,
                'limit' => $this->maxEmailsPerHour
            ]);
            return false;
        }
        
        if ($dayCount >= $this->maxEmailsPerDay) {
            Log::error('🚫 GMAIL DAILY LIMIT REACHED', [
                'sent' => $dayCount,
                'limit' => $this->maxEmailsPerDay,
                'message' => 'Wait 24 hours or switch to different SMTP provider'
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * ✅ INCREMENT: Email counters with auto-expire
     */
    private function incrementEmailCount($recipientCount = 1)
    {
        $hourKey = 'gmail_count_hour_' . now()->format('Y-m-d-H');
        $dayKey = 'gmail_count_day_' . now()->format('Y-m-d');
        
        // Increment or create
        $hourCount = Cache::get($hourKey, 0) + $recipientCount;
        $dayCount = Cache::get($dayKey, 0) + $recipientCount;
        
        // Store with expiry
        Cache::put($hourKey, $hourCount, 3600); // 1 hour
        Cache::put($dayKey, $dayCount, 86400);   // 24 hours
        
        Log::info('📧 Email sent', [
            'recipients' => $recipientCount,
            'hour_total' => $hourCount,
            'day_total' => $dayCount,
            'hour_remaining' => $this->maxEmailsPerHour - $hourCount,
            'day_remaining' => $this->maxEmailsPerDay - $dayCount
        ]);
    }

    /**
     * ✅ SAFE SEND: Wrapper to prevent limit issues
     */
    private function safeSend($mailable, $recipient, $context = [])
    {
        try {
            if (!$this->canSendEmail()) {
                Log::warning('⚠️ Email not sent - rate limit', array_merge($context, [
                    'recipient' => $recipient
                ]));
                return false;
            }

            Mail::to($recipient)->send($mailable);
            $this->incrementEmailCount(1);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('❌ Email send failed', array_merge($context, [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]));
            
            // If it's a Gmail limit error, log it specially
            if (str_contains($e->getMessage(), '550-5.4.5')) {
                Log::critical('🚫 GMAIL DAILY LIMIT HIT - Wait 24 hours');
            }
            
            return false;
        }
    }

    /**
     * Send notification when a new Sales Order is created
     */
    public function notifyNewSalesOrder($salesOrder)
    {
        $key = "so_created_{$salesOrder->id}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = $this->getRecipientsByRole(['IT']);
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients found for new SO notification');
                return false;
            }
            
            $data = [
                'title' => 'New Sales Order Created',
                'emailMessage' => "A new sales order has been created and is pending approval.",
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
                'total_amount' => number_format($salesOrder->total_amount, 2),
                'status' => $salesOrder->status,
                'created_by' => $salesOrder->preparer->name ?? 'System',
                'created_at' => $salesOrder->created_at->format('M d, Y h:i A'),
                'view_url' => route('sales_orders.show', $salesOrder->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients as $recipient) {
                if ($this->safeSend(new SalesOrderCreated($data), $recipient->email, [
                    'type' => 'so_created',
                    'so_number' => $salesOrder->sales_order_number
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ New Sales Order notifications', [
                'so_number' => $salesOrder->sales_order_number,
                'sent' => $sentCount,
                'total_recipients' => $recipients->count()
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send Sales Order notification', [
                'error' => $e->getMessage(),
                'so_number' => $salesOrder->sales_order_number ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Send notification when Sales Order status changes
     */
    public function notifySalesOrderStatusChange($salesOrder, $oldStatus, $newStatus)
    {
        $key = "so_status_{$salesOrder->id}_{$oldStatus}_{$newStatus}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($salesOrder->preparer) {
                $recipients->push($salesOrder->preparer);
            }
            
            if ($newStatus === 'Approved') {
                $roleRecipients = $this->getRecipientsByRole(['IT']);
                $recipients = $recipients->merge($roleRecipients);
            }
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for SO status change');
                return false;
            }
            
            $data = [
                'title' => 'Sales Order Status Updated',
                'emailMessage' => "Sales Order status has been changed from {$oldStatus} to {$newStatus}.",
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => auth()->user()->name ?? 'System',
                'notes' => $salesOrder->notes ?? null,
                'view_url' => route('sales_orders.show', $salesOrder->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new SalesOrderStatusChanged($data), $recipient->email, [
                    'type' => 'so_status_change',
                    'so_number' => $salesOrder->sales_order_number
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Sales Order status change notifications', [
                'so_number' => $salesOrder->sales_order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send SO status notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification when Sales Order is updated
     */
    public function notifySalesOrderUpdated($salesOrder)
    {
        $key = "so_updated_{$salesOrder->id}_" . $salesOrder->updated_at->timestamp;
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($salesOrder->preparer) {
                $recipients->push($salesOrder->preparer);
            }
            
            $recipients = $recipients->merge($this->getRecipientsByRole(['IT']));
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for SO update');
                return false;
            }
            
            $data = [
                'title' => 'Sales Order Updated',
                'emailMessage' => 'A sales order has been updated.',
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
                'total_amount' => number_format($salesOrder->total_amount, 2),
                'updated_by' => auth()->user()->name ?? 'System',
                'updated_at' => now()->format('M d, Y h:i A'),
                'view_url' => route('sales_orders.show', $salesOrder->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new SalesOrderUpdated($data), $recipient->email, [
                    'type' => 'so_updated',
                    'so_number' => $salesOrder->sales_order_number
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Sales Order update notifications', [
                'so_number' => $salesOrder->sales_order_number,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send SO update notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification when Sales Order is closed
     */
    public function notifySalesOrderClosed($salesOrder)
    {
        $key = "so_closed_{$salesOrder->id}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($salesOrder->preparer) {
                $recipients->push($salesOrder->preparer);
            }
            
            $recipients = $recipients->merge($this->getRecipientsByRole([ 'IT']));
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for SO closed');
                return false;
            }
            
            $emailData = [
                'title' => 'Sales Order Closed',
                'emailMessage' => 'Sales Order has been closed. All items have been fully delivered.',
                'sales_order_number' => $salesOrder->sales_order_number,
                'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
                'total_amount' => number_format($salesOrder->total_amount, 2),
                'closed_by' => auth()->user()->name ?? 'System',
                'closed_at' => now()->format('M d, Y h:i A'),
                'view_url' => route('sales_orders.show', $salesOrder->id)
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new SalesOrderClosed($emailData), $recipient->email, [
                    'type' => 'so_closed',
                    'so_number' => $salesOrder->sales_order_number
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Sales Order closed notifications', [
                'so_number' => $salesOrder->sales_order_number,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send SO closed notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Send notification when Delivery status changes
     */
    public function notifyDeliveryStatusChange($delivery, $action)
    {
        $key = "delivery_status_{$delivery->id}_{$action}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($delivery->created_by) {
                $creator = User::where('name', $delivery->created_by)->first();
                if ($creator) {
                    $recipients->push($creator);
                }
            }
            
            if ($action === 'approved') {
                $recipients = $recipients->merge($this->getRecipientsByRole([ 'IT']));
            }
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for delivery status change');
                return false;
            }
            
            $data = [
                'title' => "Delivery " . ucfirst($action),
                'emailMessage' => "Delivery has been {$action}.",
                'dr_no' => $delivery->dr_no,
                'sales_order_number' => $delivery->sales_order_number,
                'customer_name' => $delivery->customer_name ?? 'N/A',
                'action' => $action,
                'actioned_by' => auth()->user()->name ?? 'System',
                'rejection_reason' => $delivery->rejection_reason ?? null,
                'view_url' => route('deliveries.show', $delivery->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new DeliveryStatusChanged($data), $recipient->email, [
                    'type' => 'delivery_status',
                    'dr_no' => $delivery->dr_no
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Delivery status change notifications', [
                'dr_no' => $delivery->dr_no,
                'action' => $action,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send Delivery status notification', [
                'error' => $e->getMessage(),
                'dr_no' => $delivery->dr_no ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Send notification when a new Delivery is created
     */
    public function notifyNewDelivery($delivery)
    {
        $key = "delivery_created_{$delivery->id}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = $this->getRecipientsByRole([ 'IT']);
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for new delivery');
                return false;
            }
            
            $data = [
                'title' => 'New Delivery Pending Approval',
                'emailMessage' => 'A new delivery has been created and requires approval.',
                'dr_no' => $delivery->dr_no,
                'sales_order_number' => $delivery->sales_order_number,
                'customer_name' => $delivery->customer_name ?? 'N/A',
                'delivery_batch' => $delivery->delivery_batch,
                'status' => $delivery->status,
                'approval_status' => $delivery->approval_status,
                'created_by' => $delivery->created_by ?? 'System',
                'view_url' => route('deliveries.show', $delivery->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients as $recipient) {
                if ($this->safeSend(new DeliveryCreated($data), $recipient->email, [
                    'type' => 'delivery_created',
                    'dr_no' => $delivery->dr_no
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ New delivery notifications', [
                'dr_no' => $delivery->dr_no,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send new delivery notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification when Delivery is updated
     */
    public function notifyDeliveryUpdated($delivery)
    {
        $key = "delivery_updated_{$delivery->id}_" . ($delivery->updated_at ? $delivery->updated_at->timestamp : time());
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($delivery->created_by) {
                $creator = User::where('name', $delivery->created_by)->first();
                if ($creator) {
                    $recipients->push($creator);
                }
            }
            
            $recipients = $recipients->merge($this->getRecipientsByRole(['IT']));
            
            if ($recipients->isEmpty()) {
                return false;
            }
            
            $data = [
                'title' => 'Delivery Updated',
                'emailMessage' => 'A delivery has been updated.',
                'dr_no' => $delivery->dr_no,
                'sales_order_number' => $delivery->sales_order_number,
                'customer_name' => $delivery->customer_name ?? 'N/A',
                'updated_by' => auth()->user()->name ?? 'System',
                'view_url' => route('deliveries.show', $delivery->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new DeliveryUpdated($data), $recipient->email, [
                    'type' => 'delivery_updated',
                    'dr_no' => $delivery->dr_no
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Delivery update notifications', [
                'dr_no' => $delivery->dr_no,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send delivery update notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification when a new Item is created
     */
    public function notifyNewItem($item)
    {
        $key = "item_created_{$item->id}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = $this->getRecipientsByRole([ 'IT']);
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for new item');
                return false;
            }
            
            $data = [
                'title' => 'New Item Pending Approval',
                'emailMessage' => 'A new item has been added and requires approval.',
                'item_code' => $item->item_code,
                'item_description' => $item->item_description ?? 'N/A',
                'brand' => $item->brand ?? 'N/A',
                'category' => $item->item_category ?? 'N/A',
                'created_by' => auth()->user()->name ?? 'System',
                'view_url' => route('items.pending'),
            ];
            
            $sentCount = 0;
            foreach ($recipients as $recipient) {
                if ($this->safeSend(new ItemCreated($data), $recipient->email, [
                    'type' => 'item_created',
                    'item_code' => $item->item_code
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ New Item notifications', [
                'item_code' => $item->item_code,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send Item notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification when Item status changes
     */
    public function notifyItemStatusChange($item, $action)
    {
        $key = "item_status_{$item->id}_{$action}";
        if ($this->isDuplicateNotification($key)) {
            return true;
        }

        try {
            $recipients = collect();
            
            if ($item->added_by) {
                $creator = User::find($item->added_by);
                if ($creator) {
                    $recipients->push($creator);
                }
            }
            
            if ($recipients->isEmpty()) {
                Log::warning('No recipients for item status change');
                return false;
            }
            
            $data = [
                'title' => "Item " . ucfirst($action),
                'emailMessage' => "Your item has been {$action}.",
                'item_code' => $item->item_code,
                'item_description' => $item->item_description ?? 'N/A',
                'brand' => $item->brand ?? 'N/A',
                'category' => $item->item_category ?? 'N/A',
                'action' => $action,
                'actioned_by' => auth()->user()->name ?? 'System',
                'rejection_reason' => $item->rejection_reason ?? null,
                'view_url' => route('items.show', $item->id),
            ];
            
            $sentCount = 0;
            foreach ($recipients->unique('id') as $recipient) {
                if ($this->safeSend(new ItemStatusChanged($data), $recipient->email, [
                    'type' => 'item_status',
                    'item_code' => $item->item_code
                ])) {
                    $sentCount++;
                }
            }
            
            Log::info('✅ Item status change notifications', [
                'item_code' => $item->item_code,
                'action' => $action,
                'sent' => $sentCount
            ]);
            
            return $sentCount > 0;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send Item status notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Helper: Get users by role
     */
    private function getRecipientsByRole(array $roles)
    {
        return User::whereIn('role', $roles)
                   ->whereNotNull('email')
                   ->get();
    }
}