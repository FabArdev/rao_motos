<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago_cuota', function (Blueprint $table) {
            $table->timestamp('pago_facil_expires_at')->nullable()->after('pago_facil_qr_image');
        });
    }

    public function down(): void
    {
        Schema::table('pago_cuota', function (Blueprint $table) {
            $table->dropColumn('pago_facil_expires_at');
        });
    }
};
