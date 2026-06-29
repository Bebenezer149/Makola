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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_id');
            $table->string('customer_name');
            $table->string('phone_number');
            $table->string('delivery_to');
            $table->string('additional_notes');
            $table->enum('status',['AVAILABLE','OUT_OF_STOCK']);
            $table->enum('payment_method', ['MOMO', 'CASH']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
