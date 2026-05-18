<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('live_chicken_id')->nullable()->after('srr_id');
            $table->foreign('live_chicken_id')->references('id')->on('live_chickens')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropForeign(['live_chicken_id']);
            $table->dropColumn('live_chicken_id');
        });
    }
};
