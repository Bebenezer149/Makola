<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\facades\DB;
use Illuminate\Support\facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On Postgres, `orders.status` can have an existing CHECK constraint (orders_status_check)
        // that may not include the status values your app uses.
        // We explicitly drop and recreate it with the allowed set.
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');

        DB::statement(
            "ALTER TABLE orders\n            ADD CONSTRAINT orders_status_check\n            CHECK (status IN ('PENDING','Delivered','Confirmed','Cancelled','Pending'))"
        );

        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('PENDING')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'AVAILABLE',
                'OUT_OF_STOCK',
            ])->change();
        });
    }
};
