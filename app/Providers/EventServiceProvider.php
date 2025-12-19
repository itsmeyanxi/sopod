<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\SalesOrderChanged;
use App\Listeners\NotifyUsersOfSalesOrderChange;
use App\Models\SalesOrder;
use App\Models\Deliveries;
use App\Models\Item;
use App\Observers\SalesOrderObserver;
use App\Observers\DeliveryObserver;
use App\Observers\ItemObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SalesOrderChanged::class => [
            NotifyUsersOfSalesOrderChange::class,
        ],
    ];

    public function boot(): void
    {
        // Register observers for email notifications
        SalesOrder::observe(SalesOrderObserver::class);
        Deliveries::observe(DeliveryObserver::class); 
        Item::observe(ItemObserver::class);
    }
}