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
        // Add approval fields to purchase_requests
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('approved_by')
                  ->nullable()
                  ->after('status')
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
        });

        // Add approval fields to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('approved_by')
                  ->nullable()
                  ->after('status')
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
        });

        // Add approval fields to request_for_payments
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->foreignId('approved_by')
                  ->nullable()
                  ->after('status')
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
        });

        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
