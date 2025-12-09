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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Product snapshot
            $table->string('product_name');
            $table->string('product_code')->nullable(); // tambahan

            // Variant (optional)
            $table->string('size')->nullable();
            $table->string('color')->nullable();

            // Quantity & price
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('total_price')->default(0);

            $table->foreignId('series_id')
                ->nullable()
                ->constrained('series')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
