<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use PHP to handle the collation issue
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            // Update ar_aging records for this customer
            DB::table('ar_aging')
                ->where('customer_code', $customer->customer_code)
                ->whereNull('collection_terms')
                ->update(['collection_terms' => $customer->collection_terms]);
        }

        // Update sales_orders based on customer_id
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            DB::table('sales_orders')
                ->where('customer_id', $customer->id)
                ->whereNull('collection_terms')
                ->update(['collection_terms' => $customer->collection_terms]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the collection_terms that were populated
        DB::statement('UPDATE ar_aging SET collection_terms = NULL');
        DB::statement('UPDATE sales_orders SET collection_terms = NULL');
    }
};
