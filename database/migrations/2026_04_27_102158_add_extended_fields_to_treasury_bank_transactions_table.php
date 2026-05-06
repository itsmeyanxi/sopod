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
        Schema::table('treasury_bank_transactions', function (Blueprint $table) {
            $table->string('check_number')->nullable()->after('reference');
            $table->string('currency', 10)->default('PHP')->after('check_number');
            $table->decimal('exchange_rate', 10, 4)->default(1)->after('currency');
            $table->decimal('amount_php', 18, 2)->nullable()->after('exchange_rate');
            $table->string('dr_account')->nullable()->after('amount_php');
            $table->string('cr_account')->nullable()->after('dr_account');
        });
    }

    public function down(): void
    {
        Schema::table('treasury_bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['check_number', 'currency', 'exchange_rate', 'amount_php', 'dr_account', 'cr_account']);
        });
    }
};
