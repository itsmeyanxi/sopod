<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->integer('item_no');
            $table->string('item_code')->nullable();
            $table->decimal('qty', 10, 2);
            $table->string('uom');
            $table->text('description');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
