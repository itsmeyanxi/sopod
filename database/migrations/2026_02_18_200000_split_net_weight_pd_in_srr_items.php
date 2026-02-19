<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_receiving_report_items', function (Blueprint $table) {
            $table->renameColumn('net_weight_pd', 'net_weight');
        });

        Schema::table('supplier_receiving_report_items', function (Blueprint $table) {
            $table->date('pd')->nullable()->after('net_weight');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_receiving_report_items', function (Blueprint $table) {
            $table->dropColumn('pd');
        });

        Schema::table('supplier_receiving_report_items', function (Blueprint $table) {
            $table->renameColumn('net_weight', 'net_weight_pd');
        });
    }
};
