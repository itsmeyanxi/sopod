<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('gl_account_id')->nullable()->after('bank');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('gl_account_id');

            $table->foreign('gl_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('treasury_bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->dropForeign(['gl_account_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['gl_account_id', 'bank_account_id']);
        });
    }
};
