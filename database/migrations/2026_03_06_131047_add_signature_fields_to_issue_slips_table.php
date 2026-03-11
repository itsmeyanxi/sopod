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
        Schema::table('issue_slips', function (Blueprint $table) {
            $table->string('issued_by')->nullable()->after('remarks');
            $table->string('transport')->nullable()->after('issued_by');
            $table->string('service_providers_checker')->nullable()->after('transport');
            $table->string('received_by')->nullable()->after('service_providers_checker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_slips', function (Blueprint $table) {
            $table->dropColumn(['issued_by', 'transport', 'service_providers_checker', 'received_by']);
        });
    }
};
