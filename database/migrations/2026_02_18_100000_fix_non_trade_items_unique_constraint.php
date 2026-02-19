<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            // Drop the unique constraint on name alone
            $table->dropUnique(['name']);

            // Add unique constraint on (name, supplier_id) pair
            // This allows the same item to be linked to different suppliers as separate records
            $table->unique(['name', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::table('non_trade_items', function (Blueprint $table) {
            $table->dropUnique(['name', 'supplier_id']);
            $table->unique('name');
        });
    }
};
