<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add collection_terms to ar_aging table to preserve terms at time of invoice
        Schema::table('ar_aging', function (Blueprint $table) {
            if (!Schema::hasColumn('ar_aging', 'collection_terms')) {
                $table->string('collection_terms')->nullable()->after('customer_code');
            }
        });

        // Add collection_terms to sales_orders table to preserve terms at time of order
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'collection_terms')) {
                $table->string('collection_terms')->nullable()->after('customer_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_aging', function (Blueprint $table) {
            $table->dropColumn('collection_terms');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('collection_terms');
        });
    }
};
