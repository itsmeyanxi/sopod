<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->string('approval_stage')->default('pending_accounting')->after('status');

            // Accounting Manager review
            $table->unsignedBigInteger('accounting_reviewed_by')->nullable()->after('approval_stage');
            $table->timestamp('accounting_reviewed_at')->nullable()->after('accounting_reviewed_by');
            $table->decimal('accounting_reviewed_latitude', 10, 8)->nullable()->after('accounting_reviewed_at');
            $table->decimal('accounting_reviewed_longitude', 11, 8)->nullable()->after('accounting_reviewed_latitude');
            $table->string('accounting_reviewed_location')->nullable()->after('accounting_reviewed_longitude');

            // Final approval geolocation (approval_user_id and approval_date already exist)
            $table->decimal('approved_latitude', 10, 8)->nullable()->after('approval_date');
            $table->decimal('approved_longitude', 11, 8)->nullable()->after('approved_latitude');
            $table->string('approved_location')->nullable()->after('approved_longitude');

            $table->foreign('accounting_reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Update existing approved records
        \DB::table('check_vouchers')->where('status', 'approved')->update(['approval_stage' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->dropForeign(['accounting_reviewed_by']);
            $table->dropColumn([
                'approval_stage',
                'accounting_reviewed_by', 'accounting_reviewed_at',
                'accounting_reviewed_latitude', 'accounting_reviewed_longitude', 'accounting_reviewed_location',
                'approved_latitude', 'approved_longitude', 'approved_location',
            ]);
        });
    }
};
