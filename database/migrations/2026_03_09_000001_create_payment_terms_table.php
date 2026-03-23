<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description', 100);
            $table->integer('net_days');
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->integer('discount_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
