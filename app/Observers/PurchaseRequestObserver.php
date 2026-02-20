<?php

namespace App\Observers;

use App\Models\PurchaseRequest;

class PurchaseRequestObserver
{
    /**
     * Handle the "updating" event - prevent changes to approved_at once it's set
     */
    public function updating(PurchaseRequest $purchaseRequest)
    {
        // If the PR is already approved or rejected, prevent any changes to approval fields
        if ($purchaseRequest->getOriginal('approved_at') !== null) {
            // Lock the approval timestamp and approver
            if ($purchaseRequest->isDirty('approved_at')) {
                $purchaseRequest->approved_at = $purchaseRequest->getOriginal('approved_at');
            }

            if ($purchaseRequest->isDirty('approved_by')) {
                $purchaseRequest->approved_by = $purchaseRequest->getOriginal('approved_by');
            }

            // Lock the approval coordinates (immutable signature location)
            if ($purchaseRequest->isDirty('approved_latitude')) {
                $purchaseRequest->approved_latitude = $purchaseRequest->getOriginal('approved_latitude');
            }

            if ($purchaseRequest->isDirty('approved_longitude')) {
                $purchaseRequest->approved_longitude = $purchaseRequest->getOriginal('approved_longitude');
            }

            if ($purchaseRequest->isDirty('approved_location')) {
                $purchaseRequest->approved_location = $purchaseRequest->getOriginal('approved_location');
            }

            // Prevent status change from approved/rejected back to pending
            if ($purchaseRequest->isDirty('status')) {
                $originalStatus = $purchaseRequest->getOriginal('status');
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    $purchaseRequest->status = $originalStatus;
                }
            }
        }
    }

    /**
     * Handle the "updated" event
     */
    public function updated(PurchaseRequest $purchaseRequest)
    {
        //
    }
}
