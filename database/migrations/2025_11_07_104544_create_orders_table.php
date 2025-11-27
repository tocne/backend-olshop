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

            // Member → terisi | Guest → null
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Guest checkout → terisi | Member → boleh null
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();

            // Catatan tambahan dari guest/member
            $table->text('notes')->nullable();

            // Total harga dari front-end
            $table->integer('subtotal')->default(0);

            // pending, paid, shipped, delivered, cancelled
            $table->string('status')->default('pending');

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
