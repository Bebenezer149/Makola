<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orderitems', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->change();
            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('vendor_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['vendor_id', 'created_at']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('link');
        });
    }

    public function down(): void
    {
        Schema::table('orderitems', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['product_id']);
            $table->integer('subtotal')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'created_at']);
            $table->dropIndex(['vendor_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['link']);
        });
    }
};
