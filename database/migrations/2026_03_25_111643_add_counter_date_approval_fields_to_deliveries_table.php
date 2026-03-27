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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->boolean('counter_date_approved')->default(false)->after('counter_date');
            $table->string('counter_date_approved_by')->nullable()->after('counter_date_approved');
            $table->timestamp('counter_date_approved_at')->nullable()->after('counter_date_approved_by');
            $table->string('counter_date_attachment_path')->nullable()->after('counter_date_approved_at');
            $table->string('counter_date_attachment_name')->nullable()->after('counter_date_attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'counter_date_approved',
                'counter_date_approved_by',
                'counter_date_approved_at',
                'counter_date_attachment_path',
                'counter_date_attachment_name',
            ]);
        });
    }
};
