<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issue_slips', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('customer_name')->constrained('customers')->nullOnDelete();
            $table->string('destination')->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('issue_slips', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'destination']);
        });
    }
};
