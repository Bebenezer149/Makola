<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique();
            $table->string('idempotency_key', 100)->nullable();
            $table->unique(['vendor_id', 'idempotency_key'], 'payouts_vendor_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropUnique('payouts_vendor_idempotency_unique');
            $table->dropColumn(['reference', 'idempotency_key']);
        });
    }
};
