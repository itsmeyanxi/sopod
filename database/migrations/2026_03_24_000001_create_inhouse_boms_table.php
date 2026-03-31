<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inhouse_boms')) {
            Schema::create('inhouse_boms', function (Blueprint $table) {
                $table->id();
                $table->string('cycle_ref')->unique();
                $table->date('cycle_date')->nullable();
                $table->string('grower')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('num_houses')->default(1);
                $table->string('status')->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inhouse_boms');
    }
};
