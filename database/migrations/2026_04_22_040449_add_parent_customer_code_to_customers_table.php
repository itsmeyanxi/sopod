<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('parent_customer_code', 50)->nullable()->after('customer_code')->index();
        });

        // Auto-backfill: set parent_customer_code to base code + '-000'
        // e.g. C0000000193-001 → C0000000193-000
        DB::statement("
            UPDATE customers
            SET parent_customer_code = CONCAT(SUBSTRING_INDEX(customer_code, '-', 1), '-000')
            WHERE customer_code IS NOT NULL AND customer_code != ''
        ");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('parent_customer_code');
        });
    }
};
