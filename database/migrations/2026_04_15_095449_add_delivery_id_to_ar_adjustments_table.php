<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_id')->nullable()->after('receiving_report_id');
            $table->index('delivery_id');
        });
    }

    public function down(): void
    {
        Schema::table('ar_adjustments', function (Blueprint $table) {
            $table->dropIndex(['delivery_id']);
            $table->dropColumn('delivery_id');
        });
    }
};
