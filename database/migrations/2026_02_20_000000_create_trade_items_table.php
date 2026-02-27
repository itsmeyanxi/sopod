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
        Schema::create('trade_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor_code')->nullable();
            $table->string('account')->nullable(); // Accounts Payable - Trade - Local, Accounts Payable - Trade - Import
            $table->string('local_or_import')->nullable(); // 'Local' or 'Import'
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('item_code')->nullable();
            $table->timestamps();

            // Index for faster searches
            $table->index('name');
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_items');
    }
};
