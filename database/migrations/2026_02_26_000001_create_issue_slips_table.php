<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_slips', function (Blueprint $table) {
            $table->id();
            $table->string('issue_slip_number')->unique();
            $table->date('date');
            $table->string('origin')->nullable();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->string('sales_order_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('issue_slip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_slip_id')->constrained('issue_slips')->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->nullable()->constrained('sales_order_items')->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->string('item_description')->nullable();
            $table->string('brand')->nullable();
            $table->string('item_category')->nullable();
            $table->decimal('so_quantity', 10, 2)->default(0);
            $table->decimal('number_of_boxes', 10, 2)->default(0);
            $table->decimal('net_weight', 10, 4)->default(0);
            $table->decimal('actual_weight', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_slip_items');
        Schema::dropIfExists('issue_slips');
    }
};
