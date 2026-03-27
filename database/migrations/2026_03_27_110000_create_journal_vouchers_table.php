<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('jv_number')->unique();
            $table->date('jv_date');
            $table->string('transaction_type'); // bank_interest, bank_charges, reclassification, adjustment, correction, accrual, reversal, other
            $table->text('description');
            $table->string('reference_no')->nullable();
            $table->string('department')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->string('status')->default('Draft'); // Draft, Posted, Void
            $table->text('remarks')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained()->onDelete('cascade');
            $table->string('account_code');
            $table->string('account_name')->nullable();
            $table->text('line_description')->nullable();
            $table->string('cost_center')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_voucher_lines');
        Schema::dropIfExists('journal_vouchers');
    }
};
