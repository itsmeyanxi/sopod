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
        Schema::table('issue_slip_items', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('item_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_slip_items', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
