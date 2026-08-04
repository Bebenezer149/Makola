<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The application has always used the `orderitems` table. This
        // migration must remain a no-op so fresh PostgreSQL installations do
        // not attempt to alter the non-existent `order_items` table.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema change was made.
    }
};
