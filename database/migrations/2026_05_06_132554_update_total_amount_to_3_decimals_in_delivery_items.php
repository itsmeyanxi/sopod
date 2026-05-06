<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE delivery_items MODIFY total_amount DECIMAL(15,3) NOT NULL DEFAULT 0.000');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE delivery_items MODIFY total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    }
};
