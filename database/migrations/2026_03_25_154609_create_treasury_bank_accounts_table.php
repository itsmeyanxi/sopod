<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('short_name')->nullable();
            $table->enum('currency', ['PHP', 'USD'])->default('PHP');
            $table->enum('account_type', ['SA', 'CA'])->nullable();
            $table->decimal('cash_balance', 18, 2)->default(0);
            $table->date('balance_as_of')->nullable();
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->index(['currency', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_bank_accounts');
    }
};
