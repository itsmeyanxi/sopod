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
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('cost_center_code')->unique();
            $table->string('cost_center_name');
            $table->string('dimension')->nullable();
            $table->string('cost_center_owner')->nullable();
            // General CC
            $table->string('division')->nullable();
            $table->string('department')->nullable();
            $table->string('cc')->nullable();
            $table->json('gl_accounts')->nullable();
            $table->text('lookup')->nullable();
            $table->text('opex_mapping')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
