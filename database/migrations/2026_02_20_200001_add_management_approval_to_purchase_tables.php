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
            $table->unsignedBigInteger('management_approved_by')->nullable()->after('department_head_approved_location');
            $table->timestamp('management_approved_at')->nullable()->after('management_approved_by');
            $table->decimal('management_approved_latitude', 10, 8)->nullable()->after('management_approved_at');
            $table->decimal('management_approved_longitude', 11, 8)->nullable()->after('management_approved_latitude');
            $table->string('management_approved_location')->nullable()->after('management_approved_longitude');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('management_approved_by')->nullable()->after('department_head_approved_location');
            $table->timestamp('management_approved_at')->nullable()->after('management_approved_by');
            $table->decimal('management_approved_latitude', 10, 8)->nullable()->after('management_approved_at');
            $table->decimal('management_approved_longitude', 11, 8)->nullable()->after('management_approved_latitude');
            $table->string('management_approved_location')->nullable()->after('management_approved_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'management_approved_by',
                'management_approved_at',
                'management_approved_latitude',
                'management_approved_longitude',
                'management_approved_location'
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'management_approved_by',
                'management_approved_at',
                'management_approved_latitude',
                'management_approved_longitude',
                'management_approved_location'
            ]);
        });
    }
};
