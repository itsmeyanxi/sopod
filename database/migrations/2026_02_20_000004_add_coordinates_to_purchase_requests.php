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
            // Store GPS coordinates when document is approved/rejected
            $table->decimal('approved_latitude', 10, 8)->nullable()->after('approved_at');
            $table->decimal('approved_longitude', 11, 8)->nullable()->after('approved_latitude');
            $table->string('approved_location')->nullable()->after('approved_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_latitude', 'approved_longitude', 'approved_location']);
        });
    }
};
