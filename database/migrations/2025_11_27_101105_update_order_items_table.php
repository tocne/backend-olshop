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
        Schema::table('order_items', function (Blueprint $table) {

            if (! Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name');
            }

            if (! Schema::hasColumn('order_items', 'size')) {
                $table->string('size')->nullable();
            }

            if (! Schema::hasColumn('order_items', 'price')) {
                $table->integer('price')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop hanya jika sebelumnya dibuat oleh migration ini
            if (Schema::hasColumn('order_items', 'product_name')) {
                $table->dropColumn('product_name');
            }
            if (Schema::hasColumn('order_items', 'size')) {
                $table->dropColumn('size');
            }
            if (Schema::hasColumn('order_items', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
