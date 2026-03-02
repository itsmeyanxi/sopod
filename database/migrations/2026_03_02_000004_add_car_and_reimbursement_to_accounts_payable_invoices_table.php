<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->foreignId('cash_advance_request_id')->nullable()->after('request_for_payment_id')
                ->constrained('cash_advance_requests')->nullOnDelete();
            $table->foreignId('reimbursement_form_id')->nullable()->after('cash_advance_request_id')
                ->constrained('reimbursement_forms')->nullOnDelete();
            $table->string('reference_type')->nullable()->after('reimbursement_form_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_payable_invoices', function (Blueprint $table) {
            $table->dropForeign(['cash_advance_request_id']);
            $table->dropForeign(['reimbursement_form_id']);
            $table->dropColumn(['cash_advance_request_id', 'reimbursement_form_id', 'reference_type']);
        });
    }
};
