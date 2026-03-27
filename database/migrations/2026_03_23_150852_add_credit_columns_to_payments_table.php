<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('invoice_outstanding', 15, 2)->nullable()->after('net')->comment('Outstanding balance at time of payment');
            $table->decimal('overpayment', 15, 2)->default(0)->after('invoice_outstanding')->comment('Excess amount paid beyond outstanding');
            $table->decimal('credit_applied', 15, 2)->default(0)->after('overpayment')->comment('Credit from prior overpayment applied to this payment');
            $table->unsignedBigInteger('credit_from_payment_id')->nullable()->after('credit_applied')->comment('Source payment ID of the applied credit');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['invoice_outstanding', 'overpayment', 'credit_applied', 'credit_from_payment_id']);
        });
    }
};
