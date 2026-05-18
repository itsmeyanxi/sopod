<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->string('grpo_no')->nullable()->unique()->after('id');
            $table->string('container_no')->nullable()->after('po_no');
            $table->string('pallet_no')->nullable()->after('container_no');
            $table->string('storage_name')->nullable()->after('pallet_no');
            $table->string('storage_reference_no')->nullable()->after('storage_name');
            $table->string('shipping_type')->nullable()->after('storage_reference_no');
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('received_latitude')->nullable();
            $table->string('received_longitude')->nullable();
            $table->string('received_location')->nullable();
            $table->unsignedBigInteger('grpo_approved_by_user_id')->nullable();
            $table->timestamp('grpo_approved_at')->nullable();
            $table->string('grpo_approved_latitude')->nullable();
            $table->string('grpo_approved_longitude')->nullable();
            $table->string('grpo_approved_location')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('live_chickens', function (Blueprint $table) {
            $table->dropColumn([
                'grpo_no', 'container_no', 'pallet_no', 'storage_name',
                'storage_reference_no', 'shipping_type',
                'received_by_user_id', 'received_at', 'received_latitude', 'received_longitude', 'received_location',
                'grpo_approved_by_user_id', 'grpo_approved_at', 'grpo_approved_latitude', 'grpo_approved_longitude', 'grpo_approved_location',
            ]);
        });
    }
};
