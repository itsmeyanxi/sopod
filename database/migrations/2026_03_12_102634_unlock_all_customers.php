<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Unlock all customers
        DB::table('customers')->update(['is_locked' => false]);

        // Change default value to false to prevent future locking
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: set all to locked and change default back
        DB::table('customers')->update(['is_locked' => true]);

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_locked')->default(true)->change();
        });
    }
};
