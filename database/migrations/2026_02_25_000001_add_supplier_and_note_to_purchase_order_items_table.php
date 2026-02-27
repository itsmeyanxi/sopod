<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('purchase_request_item_id')
                  ->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable()->after('supplier_id');
            $table->text('note')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_name', 'note']);
        });
    }
};
