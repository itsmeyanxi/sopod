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
            $table->unsignedBigInteger('department_head_approved_by')->nullable()->after('created_by');
            $table->timestamp('department_head_approved_at')->nullable()->after('department_head_approved_by');
            $table->decimal('department_head_approved_latitude', 10, 8)->nullable()->after('department_head_approved_at');
            $table->decimal('department_head_approved_longitude', 11, 8)->nullable()->after('department_head_approved_latitude');
            $table->string('department_head_approved_location')->nullable()->after('department_head_approved_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'department_head_approved_by',
                'department_head_approved_at',
                'department_head_approved_latitude',
                'department_head_approved_longitude',
                'department_head_approved_location'
            ]);
        });
    }
};
