<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();

            // relasi ke produk
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();

            // harga paket harus integer (lebih cocok untuk e-commerce Indonesia)
            $table->integer('price')->default(0);
            $table->string('thumbnail')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('min_stock')->nullable();
            $table->string('series_code')->unique();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('series');
    }
};
