<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->unique();
            $table->string('company');
            $table->string('requisitioner');
            $table->string('department')->nullable();
            $table->string('supplier')->nullable();
            $table->string('terms')->nullable();
            $table->text('address')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->date('date_of_request');
            $table->date('date_needed')->nullable();
            $table->string('type_of_request')->nullable();
            $table->string('with_budget')->nullable();
            $table->string('charge_to')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('reason_for_requisition')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
