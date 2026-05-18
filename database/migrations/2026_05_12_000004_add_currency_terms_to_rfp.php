<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->string('currency', 10)->default('PHP')->after('amount');
            $table->string('payment_terms', 100)->nullable()->after('payee');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'payment_terms']);
        });
    }
};
