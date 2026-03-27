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
        Schema::table('ar_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('receiving_report_id')->nullable()->after('gl_account_id');
            $table->foreign('receiving_report_id')->references('id')->on('receiving_reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ar_adjustments', function (Blueprint $table) {
            $table->dropForeign(['receiving_report_id']);
            $table->dropColumn('receiving_report_id');
        });
    }
};
