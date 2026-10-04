<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED', 'REFUNDED', 'DISPUTED'])
                ->default('PENDING')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payments')
            ->where('payment_status', 'FAILED')
            ->update(['payment_status' => 'PENDING']);

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_status', ['PENDING', 'PAID', 'REFUNDED', 'DISPUTED'])
                ->default('PENDING')
                ->change();
        });
    }
};
