<?php

namespace App\Observers;

use App\Models\ArAging;

class ArAgingObserver
{
    /**
     * Handle the ArAging "creating" event.
     * Populate collection_terms from customer record
     */
    public function creating(ArAging $arAging): void
    {
        if ($arAging->customer_code && !$arAging->collection_terms) {
            $customer = \App\Models\Customer::where('customer_code', $arAging->customer_code)->first();
            if ($customer) {
                $arAging->collection_terms = $customer->collection_terms;
            }
        }
    }

    /**
     * Handle the ArAging "created" event.
     */
    public function created(ArAging $arAging): void
    {
        //
    }

    /**
     * Handle the ArAging "updated" event.
     */
    public function updated(ArAging $arAging): void
    {
        //
    }

    /**
     * Handle the ArAging "deleted" event.
     */
    public function deleted(ArAging $arAging): void
    {
        //
    }

    /**
     * Handle the ArAging "restored" event.
     */
    public function restored(ArAging $arAging): void
    {
        //
    }

    /**
     * Handle the ArAging "force deleted" event.
     */
    public function forceDeleted(ArAging $arAging): void
    {
        //
    }
}
