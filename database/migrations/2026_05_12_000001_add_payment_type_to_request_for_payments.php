<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('srr_id');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
