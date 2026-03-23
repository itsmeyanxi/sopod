<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_materials', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_description');
            $table->string('uom')->nullable();
            $table->string('category')->nullable();
            $table->decimal('default_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_materials');
    }
};
