<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('cv_no')->unique();
            $table->foreignId('accounts_payable_invoice_id')->nullable()->constrained('accounts_payable_invoices')->onDelete('set null');
            $table->date('cv_date');
            $table->date('check_date');
            $table->string('supplier_code')->nullable();
            $table->string('supplier_name');
            $table->text('supplier_address')->nullable();
            $table->string('supplier_tin')->nullable();
            $table->string('check_no')->nullable();
            $table->string('bank')->nullable();
            $table->string('branch')->nullable();
            $table->decimal('check_amount', 15, 2);
            $table->date('payment_date')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('apv_no')->nullable();
            $table->decimal('paid_amount', 15, 2);
            $table->text('particulars');
            $table->json('journal_entries')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('received_by')->nullable();
            $table->date('date_received')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_vouchers');
    }
};
