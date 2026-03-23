<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_memos', function (Blueprint $table) {
            $table->id();
            $table->string('dm_no', 80)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('supplier_name', 200);
            $table->foreignId('invoice_id')->nullable()->constrained('accounts_payable_invoices')->onDelete('set null');
            $table->string('invoice_no', 80)->nullable();
            $table->date('memo_date');
            $table->decimal('amount', 15, 2);
            $table->string('reason', 200)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('Open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_memos');
    }
};
