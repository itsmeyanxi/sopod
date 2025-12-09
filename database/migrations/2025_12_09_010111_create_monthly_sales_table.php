<?php
// database/migrations/xxxx_xx_xx_create_monthly_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monthly_sales', function (Blueprint $table) {
            $table->id();
            $table->string('month')->unique();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('php_amount', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_sales');
    }
};