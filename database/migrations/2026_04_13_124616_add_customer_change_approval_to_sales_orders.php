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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('customer_change_approved')->default(false)->after('is_locked');
            $table->string('customer_change_approved_by')->nullable()->after('customer_change_approved');
            $table->timestamp('customer_change_approved_at')->nullable()->after('customer_change_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['customer_change_approved', 'customer_change_approved_by', 'customer_change_approved_at']);
        });
    }
};
