<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('company')->nullable()->after('status');
            $table->string('ee_id')->nullable()->after('company');
            $table->string('last_name')->nullable()->after('ee_id');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('position')->nullable()->after('middle_name');
            $table->string('department')->nullable()->after('position');
            $table->string('location')->nullable()->after('department');
            $table->string('office_address')->nullable()->after('location');
            $table->date('date_hired')->nullable()->after('office_address');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['company','ee_id','last_name','first_name','middle_name','position','department','location','office_address','date_hired']);
        });
    }
};
