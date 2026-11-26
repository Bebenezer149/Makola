<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
        });

        DB::table('orders')->update([
            'subtotal_amount' => DB::raw('total_amount'),
        ]);

        Schema::table('payouts', function (Blueprint $table) {
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('transfer_amount', 10, 2)->default(0);
        });

        DB::table('payouts')->update([
            'transfer_amount' => DB::raw('payout_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'commission_amount']);
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['service_fee', 'transfer_amount']);
        });
    }
};
