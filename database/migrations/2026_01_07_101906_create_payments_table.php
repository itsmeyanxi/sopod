<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('collection_receipt_number')->nullable();
                $table->date('collection_receipt_date')->nullable();
                $table->date('payment_posting_date')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->string('payment_option')->nullable();
                $table->text('payment_notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
