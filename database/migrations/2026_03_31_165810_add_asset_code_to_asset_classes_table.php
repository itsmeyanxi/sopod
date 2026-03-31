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
        Schema::table('asset_classes', function (Blueprint $table) {
            $table->string('asset_code')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_classes', function (Blueprint $table) {
            $table->dropColumn('asset_code');
        });
    }
};
