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
        Schema::table('payments', function (Blueprint $table) {
            // bukti transfer
            $table->string('proof_image')->nullable()->after('method');
            $table->timestamp('uploaded_at')->nullable()->after('proof_image');

            // audit & future-proof
            $table->string('reference')->nullable()->after('uploaded_at'); // QRIS ref
            $table->json('meta')->nullable()->after('reference'); // payload gateway
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'proof_image',
                'uploaded_at',
                'reference',
                'meta',
            ]);
        });
    }
};
