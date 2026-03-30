<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_classes', function (Blueprint $table) {
            $table->id();
            $table->string('asset_group');
            $table->string('asset_class');
            $table->integer('useful_life_months')->default(0);
            $table->string('gl_account')->nullable();
            $table->string('depreciation_account')->nullable();
            $table->string('dep_type')->default('Straight Line');
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_classes');
    }
};
