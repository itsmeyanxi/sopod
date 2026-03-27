<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\SalesOrder;
use App\Observers\SalesOrderObserver;
use App\Models\Customer;
use App\Observers\CustomerObserver;
use App\Models\PurchaseOrder;
use App\Observers\PurchaseOrderObserver;
use App\Models\PurchaseRequest;
use App\Observers\PurchaseRequestObserver;
use App\Models\ArAging;
use App\Observers\ArAgingObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        SalesOrder::observe(SalesOrderObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        PurchaseRequest::observe(PurchaseRequestObserver::class);
        ArAging::observe(ArAgingObserver::class);
    }
}