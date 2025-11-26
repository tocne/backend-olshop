<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('stock_type')->default('ready')->after('stock');
            $table->integer('po_estimate_days')->nullable()->after('stock_type');
            $table->text('po_notes')->nullable()->after('po_estimate_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_type', 'po_estimate_days', 'po_notes']);
        });
    }
};
