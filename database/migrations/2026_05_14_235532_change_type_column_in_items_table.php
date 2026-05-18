<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY COLUMN `type` VARCHAR(30) NOT NULL DEFAULT 'TRADE'");
        DB::statement("UPDATE items SET `type` = 'TRADE' WHERE `type` = 'trade'");
        DB::statement("UPDATE items SET `type` = 'NON-TRADE' WHERE `type` = 'non_trade'");
    }

    public function down(): void
    {
        DB::statement("UPDATE items SET `type` = 'trade' WHERE `type` = 'TRADE'");
        DB::statement("UPDATE items SET `type` = 'non_trade' WHERE `type` = 'NON-TRADE'");
        DB::statement("ALTER TABLE items MODIFY COLUMN `type` ENUM('trade','non_trade') NOT NULL DEFAULT 'trade'");
    }
};
