<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->date('txn_date');
            $table->string('type', 30);
            $table->string('reference', 80);
            $table->string('supplier_name', 200);
            $table->string('description', 300)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->index('supplier_name');
            $table->index('txn_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_ledger_entries');
    }
};
