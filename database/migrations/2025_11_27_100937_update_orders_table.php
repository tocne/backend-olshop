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
        Schema::table('orders', function (Blueprint $table) {

            // Ubah user_id menjadi nullable (guest checkout)
            $table->foreignId('user_id')->nullable()->change();

            // Tambah kolom untuk guest checkout
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // Ubah total_price agar sesuai (pakai integer subtotal)
            $table->dropColumn('total_price');
            $table->integer('subtotal')->default(0);
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->foreignId('user_id')->constrained('users')->change();

            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'address',
                'notes',
            ]);

            $table->dropColumn('subtotal');
            $table->decimal('total_price', 10, 2)->nullable();
        });
    }
};
