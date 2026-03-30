<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number')->unique();
            $table->string('period');                               // YYYY-MM format e.g. 2026-03
            $table->text('description')->nullable();
            $table->json('asset_groups')->nullable();               // groups included in the run
            $table->json('depreciation_entries')->nullable();       // snapshot of per-asset amounts
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->string('status')->default('Draft');             // Draft | Posted | Void
            $table->string('jv_number')->nullable();                // linked JV after posting
            $table->timestamp('posted_at')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('period');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_runs');
    }
};
