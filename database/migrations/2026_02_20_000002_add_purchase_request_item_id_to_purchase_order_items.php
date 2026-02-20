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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Add foreign key to track which PR item this PO item came from
            $table->foreignId('purchase_request_item_id')
                ->nullable()
                ->constrained('purchase_request_items')
                ->onDelete('set null')
                ->after('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeignKey(['purchase_request_item_id']);
            $table->dropColumn('purchase_request_item_id');
        });
    }
};
