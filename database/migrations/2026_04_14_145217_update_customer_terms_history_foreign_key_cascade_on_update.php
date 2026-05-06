<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_terms_history', function (Blueprint $table) {
            $table->dropForeign('customer_terms_history_customer_code_foreign');
            $table->foreign('customer_code')
                  ->references('customer_code')
                  ->on('customers')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('customer_terms_history', function (Blueprint $table) {
            $table->dropForeign(['customer_code']);
            $table->foreign('customer_code')
                  ->references('customer_code')
                  ->on('customers')
                  ->onDelete('cascade');
        });
    }
};
