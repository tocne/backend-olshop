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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // harga (integer, bukan decimal)
            $table->integer('price');

            // stok global (kalau nanti pakai size-level stok, ini bisa dipakai default)
            $table->integer('stock')->default(0);

            // kode produk (SKU)
            $table->string('product_code')->nullable();

            // gambar utama
            $table->string('image_url')->nullable();

            $table->boolean('is_active')->default(true);

            // tipe order yang didukung
            $table->boolean('is_pilsuk')->default(true);
            $table->boolean('is_seri')->default(false);
            $table->boolean('is_po')->default(false);

            // preorder optional fields
            $table->enum('stock_type', ['ready', 'po'])->default('ready');
            $table->integer('po_estimate_days')->nullable();
            $table->text('po_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
