<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank2')->nullable()->after('account_number');
            $table->string('account_name2')->nullable()->after('bank2');
            $table->string('account_number2')->nullable()->after('account_name2');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['bank2', 'account_name2', 'account_number2']);
        });
    }
};
