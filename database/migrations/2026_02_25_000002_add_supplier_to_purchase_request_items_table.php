<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_request_items', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('purchase_request_id')
                      ->constrained('suppliers')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_request_items', 'supplier_name')) {
                $table->string('supplier_name')->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('purchase_request_items', 'note')) {
                $table->text('note')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_request_items', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('purchase_request_items', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }
            if (Schema::hasColumn('purchase_request_items', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
