<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_for_payments', function (Blueprint $table) {
            $table->id();
            $table->string('rfp_no')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->string('company');
            $table->json('payment_methods')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('payee');
            $table->decimal('amount', 15, 2);
            $table->text('particulars')->nullable();
            $table->string('bank')->nullable();
            $table->string('apv_no')->nullable();
            $table->string('cv_no')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_for_payments');
    }
};
