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
            $table->date('clearing_date')->nullable()->after('confirmed_at');
            $table->text('bounce_reason')->nullable()->after('clearing_date');
            $table->string('bounced_by')->nullable()->after('bounce_reason');
            $table->timestamp('bounced_at')->nullable()->after('bounced_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['clearing_date', 'bounce_reason', 'bounced_by', 'bounced_at']);
        });
    }
};
