<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursement_forms', function (Blueprint $table) {
            $table->id();
            $table->string('ri_no')->unique();
            $table->string('department');
            $table->date('date_applied');
            $table->decimal('total_amount_spent', 15, 2)->default(0);
            $table->decimal('amount_to_be_reimbursed', 15, 2)->default(0);
            $table->string('submitted_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->string('approval_stage')->default('pending_dh');
            // DH approval
            $table->foreignId('dh_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dh_approved_at')->nullable();
            $table->string('dh_approved_latitude')->nullable();
            $table->string('dh_approved_longitude')->nullable();
            $table->string('dh_approved_location')->nullable();
            // Executive approval
            $table->foreignId('executive_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executive_approved_at')->nullable();
            $table->string('executive_approved_latitude')->nullable();
            $table->string('executive_approved_longitude')->nullable();
            $table->string('executive_approved_location')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reimbursement_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reimbursement_form_id')->constrained('reimbursement_forms')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->text('particulars');
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursement_form_items');
        Schema::dropIfExists('reimbursement_forms');
    }
};
