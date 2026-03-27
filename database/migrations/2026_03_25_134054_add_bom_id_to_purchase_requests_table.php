<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->after('supplier_id')
                  ->constrained('inhouse_boms')->nullOnDelete();
            $table->string('bom_cycle_ref')->nullable()->after('bom_id');
            $table->decimal('bom_total_cost', 14, 2)->nullable()->after('bom_cycle_ref');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['bom_id']);
            $table->dropColumn(['bom_id', 'bom_cycle_ref', 'bom_total_cost']);
        });
    }
};
