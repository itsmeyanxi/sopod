<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no')->unique();
            $table->date('loan_date');
            $table->string('lender_name');
            $table->text('lender_address')->nullable();
            $table->string('loan_type');                            // Term Loan, Line of Credit, Mortgage, etc.
            $table->string('purpose')->nullable();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 7, 4);                // Annual rate, e.g. 7.5000 = 7.5%
            $table->integer('loan_term_months');
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date');
            $table->string('payment_frequency')->default('Monthly'); // Monthly | Quarterly | Annually
            $table->string('gl_loan_account')->nullable();          // Loans Payable GL account
            $table->string('gl_cash_account')->nullable();          // Cash/Bank received GL account
            $table->string('gl_interest_expense')->nullable();      // Interest Expense GL account
            $table->decimal('outstanding_principal', 15, 2);
            $table->decimal('total_interest_paid', 15, 2)->default(0);
            $table->decimal('total_principal_paid', 15, 2)->default(0);
            $table->string('status')->default('Active');            // Active | Paid | Void
            $table->text('terms_conditions')->nullable();
            $table->text('remarks')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('maturity_date');
            $table->index('lender_name');
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->decimal('total_payment', 15, 2)->default(0);   // principal + interest
            $table->string('reference_no')->nullable();             // check no, voucher no, etc.
            $table->string('payment_method')->nullable();           // Cash | Check | Bank Transfer
            $table->text('remarks')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
    }
};
