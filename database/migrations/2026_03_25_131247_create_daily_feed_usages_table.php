<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_feed_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('inhouse_boms')->cascadeOnDelete();
            $table->unsignedInteger('house_number');
            $table->date('usage_date');
            $table->json('materials_used'); // [{name, category, qty_used, uom}]
            $table->text('notes')->nullable();
            $table->string('logged_by')->nullable();
            $table->timestamps();

            $table->index(['bom_id', 'house_number', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_feed_usages');
    }
};
