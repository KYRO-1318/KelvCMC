<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent|fixed
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used')->default(0);
            $table->boolean('first_order_only')->default(false);
            $table->json('product_ids')->nullable(); // null = all products
            $table->json('cycles')->nullable(); // allowed billing cycles
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link the orders.coupon_id index to the now-existing table.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
        });

        Schema::dropIfExists('coupons');
    }
};
