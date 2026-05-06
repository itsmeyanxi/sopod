<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Increase quantity columns from decimal(10,2) to decimal(10,3)
     * to support 3-decimal-place weights (e.g. 32.855 kg).
     */
    public function up(): void
    {
        // delivery_items table
        DB::statement('ALTER TABLE delivery_items MODIFY quantity DECIMAL(10,3) NOT NULL');
        DB::statement('ALTER TABLE delivery_items MODIFY original_quantity DECIMAL(10,3) NULL');
        DB::statement('ALTER TABLE delivery_items MODIFY remaining_quantity DECIMAL(10,3) NULL DEFAULT 0.000');

        // deliveries table (parent rollup)
        DB::statement('ALTER TABLE deliveries MODIFY quantity DECIMAL(10,3) NULL DEFAULT 0.000');
    }

    /**
     * Reverse back to decimal(10,2).
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE delivery_items MODIFY quantity DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE delivery_items MODIFY original_quantity DECIMAL(10,2) NULL');
        DB::statement('ALTER TABLE delivery_items MODIFY remaining_quantity DECIMAL(10,2) NULL DEFAULT 0.00');

        DB::statement('ALTER TABLE deliveries MODIFY quantity DECIMAL(10,2) NULL DEFAULT 0.00');
    }
};
