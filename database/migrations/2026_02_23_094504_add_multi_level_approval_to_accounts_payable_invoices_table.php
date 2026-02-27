<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->string('approval_stage')->default('pending_dh')->after('status');

            // Department Head review
            $table->unsignedBigInteger('department_head_approved_by')->nullable()->after('approval_stage');
            $table->timestamp('department_head_approved_at')->nullable()->after('department_head_approved_by');
            $table->decimal('department_head_approved_latitude', 10, 8)->nullable()->after('department_head_approved_at');
            $table->decimal('department_head_approved_longitude', 11, 8)->nullable()->after('department_head_approved_latitude');
            $table->string('department_head_approved_location')->nullable()->after('department_head_approved_longitude');

            // Final approval geolocation (approved_by and approved_at already exist)
            $table->decimal('approved_latitude', 10, 8)->nullable()->after('approved_at');
            $table->decimal('approved_longitude', 11, 8)->nullable()->after('approved_latitude');
            $table->string('approved_location')->nullable()->after('approved_longitude');

            $table->foreign('department_head_approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // Update existing approved records
        \DB::table('accounts_payable_invoices')->where('status', 'approved')->update(['approval_stage' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->dropForeign(['department_head_approved_by']);
            $table->dropColumn([
                'approval_stage',
                'department_head_approved_by', 'department_head_approved_at',
                'department_head_approved_latitude', 'department_head_approved_longitude', 'department_head_approved_location',
                'approved_latitude', 'approved_longitude', 'approved_location',
            ]);
        });
    }
};
