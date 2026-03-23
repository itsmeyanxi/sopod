<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code');
            $table->string('customer_name');
            $table->unsignedBigInteger('requested_by');
            $table->string('requested_by_name');
            $table->text('requested_changes');
            $table->text('reason')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('requested_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_edit_requests');
    }
};
