<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_payable_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('apv_no')->unique();
            $table->foreignId('request_for_payment_id')->nullable()->constrained('request_for_payments')->onDelete('set null');
            $table->date('apv_date');
            $table->string('payment_type');
            $table->string('vendor_code')->nullable();
            $table->string('vendor_name');
            $table->text('vendor_address')->nullable();
            $table->string('vendor_tin')->nullable();
            $table->date('document_date');
            $table->string('payment_terms')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->string('currency');
            $table->decimal('forex_rate', 10, 4)->nullable();
            $table->text('particulars');
            $table->string('item_code')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('account_code')->nullable();
            $table->string('account_name')->nullable();
            $table->decimal('total', 15, 2);
            $table->decimal('downpayment_amount', 15, 2)->nullable();
            $table->decimal('total_before_vat', 15, 2);
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('total_after_vat', 15, 2);
            $table->decimal('w_tax_amount', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2);
            $table->string('prepared_by')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable_invoices');
    }
};
