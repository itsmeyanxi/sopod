<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 10);
            $table->decimal('rate_to_php', 12, 4)->default(1.0000);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Seed default currencies
        DB::table('currencies')->insert([
            ['code' => 'PHP', 'name' => 'Philippine Peso',   'symbol' => '₱',  'rate_to_php' => 1.0000,   'created_at' => now(), 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar',         'symbol' => '$',  'rate_to_php' => 57.0000,  'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'rate_to_php' => 36.0000,  'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GBP', 'name' => 'British Pound',     'symbol' => '£',  'rate_to_php' => 72.0000,  'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro',               'symbol' => '€',  'rate_to_php' => 61.0000,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
