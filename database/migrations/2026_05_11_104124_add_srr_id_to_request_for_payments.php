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
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('srr_id')->nullable()->after('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropColumn('srr_id');
        });
    }
};
