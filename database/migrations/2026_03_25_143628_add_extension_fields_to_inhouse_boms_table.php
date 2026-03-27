<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inhouse_boms', function (Blueprint $table) {
            $table->foreignId('parent_bom_id')->nullable()->after('created_by')
                  ->constrained('inhouse_boms')->nullOnDelete();
            $table->unsignedInteger('extension_number')->nullable()->after('parent_bom_id');
        });
    }

    public function down(): void
    {
        Schema::table('inhouse_boms', function (Blueprint $table) {
            $table->dropForeign(['parent_bom_id']);
            $table->dropColumn(['parent_bom_id', 'extension_number']);
        });
    }
};
