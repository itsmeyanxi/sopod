<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_locked');
            $table->string('hidden_by')->nullable()->after('is_hidden');
            $table->timestamp('hidden_at')->nullable()->after('hidden_by');
            $table->string('hidden_reason')->nullable()->after('hidden_at');

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn(['is_hidden', 'hidden_by', 'hidden_at', 'hidden_reason']);
        });
    }
};
