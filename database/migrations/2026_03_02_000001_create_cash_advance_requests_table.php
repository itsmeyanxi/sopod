<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('car_no')->unique();
            $table->string('payee');
            $table->string('department');
            $table->text('purpose');
            $table->date('date_requested');
            $table->date('date_needed');
            $table->decimal('amount_advanced', 15, 2);
            $table->string('requested_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->string('approval_stage')->default('pending_dh');
            // DH approval (Checked by)
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
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_requests');
    }
};
