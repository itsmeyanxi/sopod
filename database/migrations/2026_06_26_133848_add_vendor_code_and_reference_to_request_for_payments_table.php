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
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->string('vendor_code')->nullable()->after('payee');
            $table->string('po_reference_number')->nullable()->after('vendor_code');
        });
    }

    public function down(): void
    {
        Schema::table('request_for_payments', function (Blueprint $table) {
            $table->dropColumn(['vendor_code', 'po_reference_number']);
        });
    }
};
