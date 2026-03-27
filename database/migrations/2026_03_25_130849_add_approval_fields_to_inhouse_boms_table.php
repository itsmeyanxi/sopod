<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inhouse_boms', function (Blueprint $table) {
            $table->boolean('approved')->default(false)->after('status');
            $table->string('approved_by')->nullable()->after('approved');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('inhouse_boms', function (Blueprint $table) {
            $table->dropColumn(['approved', 'approved_by', 'approved_at']);
        });
    }
};
