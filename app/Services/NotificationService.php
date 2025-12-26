<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
    /**
     * Send notification when a new Sales Order is created
     */
    // public function notifyNewSalesOrder($salesOrder)
    // {
    //     try {
    //         $recipients = $this->getRecipientsByRole(['IT']);
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients found for new SO notification');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'New Sales Order Created',
    //             'emailMessage' => "A new sales order has been created and is pending approval.",
    //             'sales_order_number' => $salesOrder->sales_order_number,
    //             'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
    //             'total_amount' => number_format($salesOrder->total_amount, 2),
    //             'status' => $salesOrder->status,
    //             'created_by' => $salesOrder->preparer->name ?? 'System',
    //             'created_at' => $salesOrder->created_at->format('M d, Y h:i A'),
    //             'view_url' => route('sales_orders.show', $salesOrder->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails instead of sending immediately
    //         foreach ($recipients as $recipient) {
    //             Mail::to($recipient->email)->queue(new SalesOrderCreated($data));
    //         }
            
    //         Log::info('✅ New Sales Order notifications queued', [
    //             'so_number' => $salesOrder->sales_order_number,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue Sales Order notification', [
    //             'error' => $e->getMessage(),
    //             'so_number' => $salesOrder->sales_order_number ?? 'unknown'
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when Sales Order status changes
    //  */
    // public function notifySalesOrderStatusChange($salesOrder, $oldStatus, $newStatus)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($salesOrder->preparer) {
    //             $recipients->push($salesOrder->preparer);
    //         }
            
    //         if ($newStatus === 'Approved') {
    //             $roleRecipients = $this->getRecipientsByRole(['Admin', 'IT', 'Delivery_Creator', 'Delivery_Approver']);
    //             $recipients = $recipients->merge($roleRecipients);
    //         }
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for SO status change');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'Sales Order Status Updated',
    //             'emailMessage' => "Sales Order status has been changed from {$oldStatus} to {$newStatus}.",
    //             'sales_order_number' => $salesOrder->sales_order_number,
    //             'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
    //             'old_status' => $oldStatus,
    //             'new_status' => $newStatus,
    //             'updated_by' => auth()->user()->name ?? 'System',
    //             'notes' => $salesOrder->notes ?? null,
    //             'view_url' => route('sales_orders.show', $salesOrder->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new SalesOrderStatusChanged($data));
    //         }
            
    //         Log::info('✅ Sales Order status change notifications queued', [
    //             'so_number' => $salesOrder->sales_order_number,
    //             'old_status' => $oldStatus,
    //             'new_status' => $newStatus,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue SO status notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when Sales Order is updated
    //  */
    // public function notifySalesOrderUpdated($salesOrder)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($salesOrder->preparer) {
    //             $recipients->push($salesOrder->preparer);
    //         }
            
    //         $recipients = $recipients->merge($this->getRecipientsByRole(['IT']));
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for SO update');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'Sales Order Updated',
    //             'emailMessage' => 'A sales order has been updated.',
    //             'sales_order_number' => $salesOrder->sales_order_number,
    //             'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
    //             'total_amount' => number_format($salesOrder->total_amount, 2),
    //             'updated_by' => auth()->user()->name ?? 'System',
    //             'updated_at' => now()->format('M d, Y h:i A'),
    //             'view_url' => route('sales_orders.show', $salesOrder->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new SalesOrderUpdated($data));
    //         }
            
    //         Log::info('✅ Sales Order update notifications queued', [
    //             'so_number' => $salesOrder->sales_order_number,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue SO update notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when Sales Order is closed
    //  */
    // public function notifySalesOrderClosed($salesOrder)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($salesOrder->preparer) {
    //             $recipients->push($salesOrder->preparer);
    //         }
            
    //         $recipients = $recipients->merge($this->getRecipientsByRole(['Admin', 'IT']));
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for SO closed');
    //             return false;
    //         }
            
    //         $emailData = [
    //             'title' => 'Sales Order Closed',
    //             'emailMessage' => 'Sales Order has been closed. All items have been fully delivered.',
    //             'sales_order_number' => $salesOrder->sales_order_number,
    //             'customer_name' => $salesOrder->customer->customer_name ?? 'N/A',
    //             'total_amount' => number_format($salesOrder->total_amount, 2),
    //             'closed_by' => auth()->user()->name ?? 'System',
    //             'closed_at' => now()->format('M d, Y h:i A'),
    //             'view_url' => route('sales_orders.show', $salesOrder->id)
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new SalesOrderClosed($emailData));
    //         }
            
    //         Log::info('✅ Sales Order closed notifications queued', [
    //             'so_number' => $salesOrder->sales_order_number,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue SO closed notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }
    
    // /**
    //  * Send notification when Delivery status changes
    //  */
    // public function notifyDeliveryStatusChange($delivery, $action)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($delivery->created_by) {
    //             $creator = User::where('name', $delivery->created_by)->first();
    //             if ($creator) {
    //                 $recipients->push($creator);
    //             }
    //         }
            
    //         if ($action === 'approved') {
    //             $recipients = $recipients->merge($this->getRecipientsByRole(['Admin', 'IT', 'Delivery_Approver']));
    //         }
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for delivery status change');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => "Delivery " . ucfirst($action),
    //             'emailMessage' => "Delivery has been {$action}.",
    //             'dr_no' => $delivery->dr_no,
    //             'sales_order_number' => $delivery->sales_order_number,
    //             'customer_name' => $delivery->customer_name ?? 'N/A',
    //             'action' => $action,
    //             'actioned_by' => auth()->user()->name ?? 'System',
    //             'rejection_reason' => $delivery->rejection_reason ?? null,
    //             'view_url' => route('deliveries.show', $delivery->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new DeliveryStatusChanged($data));
    //         }
            
    //         Log::info('✅ Delivery status change notifications queued', [
    //             'dr_no' => $delivery->dr_no,
    //             'action' => $action,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue Delivery status notification', [
    //             'error' => $e->getMessage(),
    //             'dr_no' => $delivery->dr_no ?? 'unknown'
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when a new Delivery is created
    //  */
    // public function notifyNewDelivery($delivery)
    // {
    //     try {
    //         $recipients = $this->getRecipientsByRole(['Admin', 'IT', 'Delivery_Approver']);
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for new delivery');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'New Delivery Pending Approval',
    //             'emailMessage' => 'A new delivery has been created and requires approval.',
    //             'dr_no' => $delivery->dr_no,
    //             'sales_order_number' => $delivery->sales_order_number,
    //             'customer_name' => $delivery->customer_name ?? 'N/A',
    //             'delivery_batch' => $delivery->delivery_batch,
    //             'status' => $delivery->status,
    //             'approval_status' => $delivery->approval_status,
    //             'created_by' => $delivery->created_by ?? 'System',
    //             'view_url' => route('deliveries.show', $delivery->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients as $recipient) {
    //             Mail::to($recipient->email)->queue(new DeliveryCreated($data));
    //         }
            
    //         Log::info('✅ New delivery notifications queued', [
    //             'dr_no' => $delivery->dr_no,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue new delivery notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when Delivery is updated
    //  */
    // public function notifyDeliveryUpdated($delivery)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($delivery->created_by) {
    //             $creator = User::where('name', $delivery->created_by)->first();
    //             if ($creator) {
    //                 $recipients->push($creator);
    //             }
    //         }
            
    //         $recipients = $recipients->merge($this->getRecipientsByRole(['IT']));
            
    //         if ($recipients->isEmpty()) {
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'Delivery Updated',
    //             'emailMessage' => 'A delivery has been updated.',
    //             'dr_no' => $delivery->dr_no,
    //             'sales_order_number' => $delivery->sales_order_number,
    //             'customer_name' => $delivery->customer_name ?? 'N/A',
    //             'updated_by' => auth()->user()->name ?? 'System',
    //             'view_url' => route('deliveries.show', $delivery->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new DeliveryUpdated($data));
    //         }
            
    //         Log::info('✅ Delivery update notifications queued', [
    //             'dr_no' => $delivery->dr_no,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue delivery update notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when a new Item is created
    //  */
    // public function notifyNewItem($item)
    // {
    //     try {
    //         $recipients = $this->getRecipientsByRole(['Admin', 'IT', 'Accounting_Approver']);
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for new item');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => 'New Item Pending Approval',
    //             'emailMessage' => 'A new item has been added and requires approval.',
    //             'item_code' => $item->item_code,
    //             'item_description' => $item->item_description ?? 'N/A',
    //             'brand' => $item->brand ?? 'N/A',
    //             'category' => $item->item_category ?? 'N/A',
    //             'created_by' => auth()->user()->name ?? 'System',
    //             'view_url' => route('items.pending'),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients as $recipient) {
    //             Mail::to($recipient->email)->queue(new ItemCreated($data));
    //         }
            
    //         Log::info('✅ New Item notifications queued', [
    //             'item_code' => $item->item_code,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue Item notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Send notification when Item status changes
    //  */
    // public function notifyItemStatusChange($item, $action)
    // {
    //     try {
    //         $recipients = collect();
            
    //         if ($item->added_by) {
    //             $creator = User::find($item->added_by);
    //             if ($creator) {
    //                 $recipients->push($creator);
    //             }
    //         }
            
    //         if ($recipients->isEmpty()) {
    //             Log::warning('No recipients for item status change');
    //             return false;
    //         }
            
    //         $data = [
    //             'title' => "Item " . ucfirst($action),
    //             'emailMessage' => "Your item has been {$action}.",
    //             'item_code' => $item->item_code,
    //             'item_description' => $item->item_description ?? 'N/A',
    //             'brand' => $item->brand ?? 'N/A',
    //             'category' => $item->item_category ?? 'N/A',
    //             'action' => $action,
    //             'actioned_by' => auth()->user()->name ?? 'System',
    //             'rejection_reason' => $item->rejection_reason ?? null,
    //             'view_url' => route('items.show', $item->id),
    //         ];
            
    //         // ✅ FIXED: Queue emails
    //         foreach ($recipients->unique('id') as $recipient) {
    //             Mail::to($recipient->email)->queue(new ItemStatusChanged($data));
    //         }
            
    //         Log::info('✅ Item status change notifications queued', [
    //             'item_code' => $item->item_code,
    //             'action' => $action,
    //             'recipients' => $recipients->count()
    //         ]);
            
    //         return true;
            
    //     } catch (\Exception $e) {
    //         Log::error('❌ Failed to queue Item status notification', [
    //             'error' => $e->getMessage()
    //         ]);
    //         return false;
    //     }
    // }

    // /**
    //  * Helper: Get users by role
    //  */
    // private function getRecipientsByRole(array $roles)
    // {
    //     return User::whereIn('role', $roles)
    //                ->whereNotNull('email')
    //                ->get();
    // }
}