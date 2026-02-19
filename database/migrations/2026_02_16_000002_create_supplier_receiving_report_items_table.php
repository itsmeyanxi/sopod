<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_receiving_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_receiving_report_id')->constrained('supplier_receiving_reports')->onDelete('cascade');
            $table->integer('item_no');
            $table->string('item_code')->nullable();
            $table->string('item_description')->nullable();
            $table->string('brand')->nullable();
            $table->integer('no_of_boxes')->default(0);
            $table->decimal('net_weight_pd', 12, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('pallet_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_receiving_report_items');
    }
};
