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
        if (Schema::hasTable('ar_adjustments')) {
            return;
        }
        Schema::create('ar_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('transaction_type', ['atd', 'offset', 'credit_memo', 'debit_memo', 'adjustment', 'write_off'])->default('adjustment');
            $table->date('transaction_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_decrease')->default(false);
            $table->string('invoice_number')->nullable();
            $table->string('dr_no')->nullable();
            $table->string('gl_account')->nullable();
            $table->string('signed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('customer_code');
            $table->index('invoice_number');
            $table->index('dr_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_adjustments');
    }
};
