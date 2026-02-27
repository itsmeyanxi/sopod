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
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('approval_stage')->default('pending_dh')->after('status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('approval_stage')->default('pending_dh')->after('status');
        });

        // Update existing approved records to have approved status
        \DB::table('purchase_requests')
            ->where('status', 'approved')
            ->update(['approval_stage' => 'approved']);

        \DB::table('purchase_orders')
            ->where('status', 'approved')
            ->update(['approval_stage' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('approval_stage');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('approval_stage');
        });
    }
};
