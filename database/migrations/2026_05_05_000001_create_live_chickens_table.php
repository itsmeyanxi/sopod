<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_chickens', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('po_no')->nullable()->index();
            $table->string('supplier');
            $table->text('items');
            $table->string('brand')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('actual_qty', 10, 2)->default(0);
            $table->date('delivery_date')->nullable();
            $table->string('docs_required_type')->nullable();  // 'file' or 'date'
            $table->string('docs_required_file')->nullable();
            $table->date('docs_required_date')->nullable();
            $table->string('docs_transmitted_type')->nullable(); // 'file' or 'date'
            $table->string('docs_transmitted_file')->nullable();
            $table->date('docs_transmitted_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('Ongoing');
            $table->string('delivery_week_no')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_chickens');
    }
};
