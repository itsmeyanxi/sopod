<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('id');
            $table->unsignedInteger('purchase_order_item_id')->nullable()->after('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn(['purchase_order_id', 'purchase_order_item_id']);
        });
    }
};
