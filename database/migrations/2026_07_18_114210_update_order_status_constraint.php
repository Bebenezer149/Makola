<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
        ALTER TABLE orders
        ADD CONSTRAINT orders_status_check
        CHECK (status IN ('PENDING','DELIVERED','CANCELLED','CONFIRMED'))
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $dropClause = $driver === 'mysql' ? 'DROP CHECK' : 'DROP CONSTRAINT';
        DB::statement("ALTER TABLE orders {$dropClause} orders_status_check");
    }
};
