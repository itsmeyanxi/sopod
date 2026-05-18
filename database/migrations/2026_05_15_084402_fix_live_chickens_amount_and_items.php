<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->decimal('amount', 20, 2)->default(0)->change();
            $table->decimal('price', 20, 2)->default(0)->change();
            $table->decimal('actual_qty', 20, 2)->default(0)->change();
            $table->json('items_data')->nullable()->after('items');
        });
    }

    public function down(): void
    {
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->default(0)->change();
            $table->decimal('price', 12, 2)->default(0)->change();
            $table->decimal('actual_qty', 10, 2)->default(0)->change();
            $table->dropColumn('items_data');
        });
    }
};
