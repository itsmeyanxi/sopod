<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_receiving_reports', function (Blueprint $table) {
            $table->id();
            $table->string('srr_code')->unique();
            $table->date('report_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('supplier_name');
            $table->string('cv_no')->nullable();
            $table->string('po_no')->nullable();
            $table->string('storage')->nullable();
            $table->string('report_type');
            $table->text('note')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('checked_by')->nullable();
            $table->string('received_by')->nullable();
            $table->string('verified_by')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_receiving_reports');
    }
};
