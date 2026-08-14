<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->renameColumn('supplier_code', 'vendor_code');
            $table->renameColumn('supplier_tin', 'vendor_tin');
        });
    }

    public function down(): void
    {
        Schema::table('check_vouchers', function (Blueprint $table) {
            $table->renameColumn('vendor_code', 'supplier_code');
            $table->renameColumn('vendor_tin', 'supplier_tin');
        });
    }
};
