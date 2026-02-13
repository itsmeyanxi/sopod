<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add approval fields to accounts_payable_invoices
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add approval fields to check_vouchers
        // Note: 'approved_by' already exists as a text field for signatures,
        // so using 'approval_user_id' for the system approval FK
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('approval_user_id')->nullable()->after('status');
            $table->timestamp('approval_date')->nullable()->after('approval_user_id');
            $table->text('rejection_reason')->nullable()->after('approval_date');

            $table->foreign('approval_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
        });

        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->dropForeign(['approval_user_id']);
            $table->dropColumn(['approval_user_id', 'approval_date', 'rejection_reason']);
        });
    }
};
