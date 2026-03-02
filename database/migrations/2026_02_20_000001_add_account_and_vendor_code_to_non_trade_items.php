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
            if (!Schema::hasColumn('non_trade_items', 'account')) {
                $table->string('account')->nullable();
            }
            if (!Schema::hasColumn('non_trade_items', 'vendor_code')) {
                $table->string('vendor_code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            $table->dropColumn(['account', 'vendor_code']);
        });
    }
};
