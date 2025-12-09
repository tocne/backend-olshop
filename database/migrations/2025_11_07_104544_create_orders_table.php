<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // USER (optional)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Order Code
            $table->string('order_code')->unique();

            // Tipe order: pilsuk (pilih suka-suka), seri, po, normal
            $table->enum('order_type', ['pilsuk', 'seri', 'normal', 'po'])
                ->default('pilsuk');

            // Status (ready stock + PO workflow)
            $table->enum('status', [
                'pending',            // Ready stock
                'paid',
                'shipped',
                'completed',
                'canceled',

                // PO Status
                'pending_po',         // Member baru membuat PO
                'waiting_production', // Barang PO sedang diproduksi
                'ready_to_ship',      // PO selesai dan siap dikirim
            ])->default('pending');

            // Customer
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // Payment
            $table->string('payment_method')->nullable();

            // Pricing
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
