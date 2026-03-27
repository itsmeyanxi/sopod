<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('serial_engine_no')->nullable();
            $table->string('asset_group'); // e.g. Transportation Equipment, Machineries And Equipment, etc.
            $table->date('date_posted')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('dep_start_date')->nullable();
            $table->date('dep_end_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->string('dep_type')->default('Straight Line');
            $table->string('reference_apv_jv')->nullable();
            $table->string('reference_reversal')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('plate_no')->nullable();
            $table->string('assigned_person')->nullable();
            $table->string('employee_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('asset_description')->nullable();
            $table->string('asset_code')->nullable();
            $table->string('gl_account')->nullable();
            $table->string('asset_class')->nullable();
            $table->string('cost_center_name')->nullable();
            $table->string('cost_center_code')->nullable();
            $table->string('depreciation_account')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->integer('year_in_service')->nullable();
            $table->integer('year_end_service')->nullable();
            $table->decimal('useful_life_years', 5, 2)->default(0);
            $table->integer('useful_life_months')->default(0);
            $table->integer('remaining_life_months')->default(0);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('yearly_depreciation', 15, 2)->default(0);
            $table->decimal('monthly_depreciation', 15, 2)->default(0);
            $table->decimal('additions', 15, 2)->default(0);
            $table->decimal('reclass_affiliates', 15, 2)->default(0);
            $table->decimal('disposal_amount', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            $table->string('status')->default('Active'); // Active, Disposed, Reclassified
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
