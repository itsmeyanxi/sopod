<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Old 'Posted' unconfirmed → 'Clearing'
        DB::table('payments')
            ->where('status', 'Posted')
            ->where('confirmed', false)
            ->update(['status' => 'Clearing']);

        // Old 'Confirmed' → 'Posted'
        DB::table('payments')
            ->where('status', 'Confirmed')
            ->update(['status' => 'Posted']);
    }

    public function down(): void
    {
        // Reverse: 'Clearing' → 'Posted'
        DB::table('payments')
            ->where('status', 'Clearing')
            ->update(['status' => 'Posted']);

        // Reverse: 'Posted' confirmed → 'Confirmed'
        DB::table('payments')
            ->where('status', 'Posted')
            ->where('confirmed', true)
            ->update(['status' => 'Confirmed']);
    }
};
