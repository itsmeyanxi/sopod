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
        Schema::table('cash_advance_requests', function (Blueprint $table) {
            $table->string('payee')->nullable()->change();
            $table->string('department')->nullable()->change();
            $table->text('purpose')->nullable()->change();
            $table->date('date_requested')->nullable()->change();
            $table->date('date_needed')->nullable()->change();
            $table->decimal('amount_advanced', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_advance_requests', function (Blueprint $table) {
            $table->string('payee')->nullable(false)->change();
            $table->string('department')->nullable(false)->change();
            $table->text('purpose')->nullable(false)->change();
            $table->date('date_requested')->nullable(false)->change();
            $table->date('date_needed')->nullable(false)->change();
            $table->decimal('amount_advanced', 15, 2)->nullable(false)->change();
        });
    }
};
