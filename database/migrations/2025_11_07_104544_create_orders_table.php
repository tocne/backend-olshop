<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Jika ada login user — guest checkout tetap jalan
            $table->foreignId('user_id')->nullable();

            // Nomor pesanan unik
            $table->string('order_code')->unique();
            $table->enum('order_type', ['suka_suka', 'seri', 'normal'])
                ->default('suka_suka');
            // Guest checkout
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // Payment
            $table->string('payment_method')->nullable();

            // Perhitungan harga
            $table->integer('subtotal')->default(0);
            $table->integer('shipping_cost')->default(0);
            $table->integer('total')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
