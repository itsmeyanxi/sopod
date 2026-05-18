<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('po_no');
        });
        Schema::table('supplier_receiving_reports', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('po_no');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
        Schema::table('supplier_receiving_reports', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
