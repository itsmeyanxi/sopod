<?php

namespace App\Observers;

use App\Models\PurchaseOrder;

class PurchaseOrderObserver
{
    /**
     * Handle the "updating" event - prevent changes to approved_at once it's set
     */
    public function updating(PurchaseOrder $purchaseOrder)
    {
        // If the PO is already approved, prevent any changes to approval fields
        if ($purchaseOrder->getOriginal('approved_at') !== null) {
            // Lock the approval timestamp and approver
            if ($purchaseOrder->isDirty('approved_at')) {
                $purchaseOrder->approved_at = $purchaseOrder->getOriginal('approved_at');
            }

            if ($purchaseOrder->isDirty('approved_by')) {
                $purchaseOrder->approved_by = $purchaseOrder->getOriginal('approved_by');
            }

            // Prevent status change from approved back to pending/other
            if ($purchaseOrder->isDirty('status')) {
                $originalStatus = $purchaseOrder->getOriginal('status');
                if ($originalStatus === 'approved') {
                    $purchaseOrder->status = 'approved';
                }
            }
        }
    }

    /**
     * Handle the "updated" event
     */
    public function updated(PurchaseOrder $purchaseOrder)
    {
        //
    }
}
