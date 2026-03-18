<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_aging', function (Blueprint $table) {
            $table->decimal('ewt', 15, 2)->nullable()->after('cwt');
            $table->decimal('annual', 15, 2)->nullable()->after('ewt');
            $table->decimal('factoring', 15, 2)->nullable()->after('annual');
            $table->decimal('factoring_interest', 15, 2)->nullable()->after('factoring');
            $table->string('others_particulars', 255)->nullable()->after('factoring_interest');
            $table->decimal('others_amount', 15, 2)->nullable()->after('others_particulars');
            $table->decimal('check_amount', 15, 2)->nullable()->after('others_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ar_aging', function (Blueprint $table) {
            $table->dropColumn([
                'ewt', 'annual', 'factoring', 'factoring_interest',
                'others_particulars', 'others_amount', 'check_amount'
            ]);
        });
    }
};
