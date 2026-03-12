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
        Schema::create('customer_terms_history', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code');
            $table->string('old_terms')->nullable();
            $table->string('new_terms')->nullable();
            $table->string('changed_by')->nullable();
            $table->timestamps();

            // Foreign key to customers
            $table->foreign('customer_code')->references('customer_code')->on('customers')->onDelete('cascade');
            $table->index('customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_terms_history');
    }
};
