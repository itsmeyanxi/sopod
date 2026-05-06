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
        Schema::create('apv_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apv_id');
            $table->text('particulars')->nullable();
            $table->string('item_code', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->boolean('vat')->default(false);
            $table->string('tax_code', 10)->nullable();
            $table->string('account_code', 50)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->foreign('apv_id')->references('id')->on('accounts_payable_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apv_items');
    }
};
