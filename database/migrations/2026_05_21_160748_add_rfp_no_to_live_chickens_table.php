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
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->string('rfp_no', 100)->nullable()->after('po_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->dropColumn('rfp_no');
        });
    }
};
