<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('liq_no')->unique();
            $table->foreignId('cash_advance_request_id')->nullable()->constrained('cash_advance_requests')->nullOnDelete();
            $table->string('name');
            $table->string('department');
            $table->date('date_applied');
            $table->decimal('total_amount_spent', 15, 2)->default(0);
            $table->string('submitted_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->string('approval_stage')->default('pending_dh');
            // DH approval (Checked by - Immediate Superior)
            $table->foreignId('dh_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dh_approved_at')->nullable();
            $table->string('dh_approved_latitude')->nullable();
            $table->string('dh_approved_longitude')->nullable();
            $table->string('dh_approved_location')->nullable();
            // Executive approval (Approved by)
            $table->foreignId('executive_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executive_approved_at')->nullable();
            $table->string('executive_approved_latitude')->nullable();
            $table->string('executive_approved_longitude')->nullable();
            $table->string('executive_approved_location')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('liquidation_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_form_id')->constrained('liquidation_forms')->onDelete('cascade');
            $table->text('particulars');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_form_items');
        Schema::dropIfExists('liquidation_forms');
    }
};
