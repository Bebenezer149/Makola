<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_id');
            $table->integer('ledger_id');
            $table->decimal('payout_amount', 10, 2);
            $table->string('receiver');
            $table->string('account_number');
            $table->enum('channel', ['1', '2', '3', '4'])->default('1');
            $table->enum('payout_status',['PENDING','RELEASED','HELD','REFUNDED','DISPUTED']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
