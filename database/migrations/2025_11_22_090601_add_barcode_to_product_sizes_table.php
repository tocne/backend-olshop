<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('size');
        });
    }

    public function down()
    {
        Schema::table('product_sizes', function (Blueprint $table) {
            if (Schema::hasColumn('product_sizes', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }
};
