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
        Schema::table('payments', function (Blueprint $table) {
            // Drop the unique constraint on collection_receipt_number
            $table->dropUnique('unique_collection_receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Re-add the unique constraint if needed
            $table->unique('collection_receipt_number', 'unique_collection_receipt_number');
        });
    }
};
