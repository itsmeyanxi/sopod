<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->date('txn_date');
            $table->enum('type', ['Deposit', 'Withdrawal', 'Transfer', 'Fee', 'Interest', 'Adjustment']);
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->string('payee_or_source')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->decimal('running_balance', 18, 2)->default(0);
            $table->string('logged_by')->nullable();
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('treasury_bank_accounts')->cascadeOnDelete();
            $table->index(['bank_account_id', 'txn_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_bank_transactions');
    }
};
