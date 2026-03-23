<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            $table->string('group', 200)->nullable()->after('item_code');
            $table->string('brand', 200)->nullable()->after('group');
            $table->string('trading_uom', 100)->nullable()->after('unit');
            $table->string('conversion', 100)->nullable()->after('trading_uom');
            $table->string('status', 50)->default('active')->after('conversion');
        });
    }

    public function down(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            $table->dropColumn(['group', 'brand', 'trading_uom', 'conversion', 'status']);
        });
    }
};
