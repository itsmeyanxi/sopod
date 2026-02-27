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
        Schema::table('non_trade_items', function (Blueprint $table) {
            if (!Schema::hasColumn('non_trade_items', 'item_code')) {
                $table->string('item_code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('non_trade_items', 'account')) {
                $table->string('account')->nullable()->after('item_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            if (Schema::hasColumn('non_trade_items', 'item_code')) {
                $table->dropColumn('item_code');
            }
            if (Schema::hasColumn('non_trade_items', 'account')) {
                $table->dropColumn('account');
            }
        });
    }
};
