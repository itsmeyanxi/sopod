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
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code')->unique();
            $table->string('account_name');
            $table->string('fs_line_item')->nullable();
            $table->text('fs_notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('account_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_accounts');
    }
};
