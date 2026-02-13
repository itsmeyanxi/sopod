<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->onDelete('set null');
            $table->string('company');
            $table->string('supplier')->nullable();
            $table->text('supplier_address')->nullable();
            $table->string('consignee')->nullable();
            $table->text('consignee_address')->nullable();
            $table->text('delivery_address')->nullable();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('location')->nullable();
            $table->string('house')->nullable();
            $table->string('pr_no')->nullable();
            $table->decimal('lc_price', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
