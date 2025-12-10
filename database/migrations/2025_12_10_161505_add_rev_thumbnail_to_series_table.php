<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('price');
            $table->boolean('active')->default(true)->after('thumbnail');
            $table->integer('min_stock')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'active', 'min_stock']);
        });
    }
};
