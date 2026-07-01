<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificaciones in-app (alertas operativas internas). Los avisos importantes
     * al cliente van por email/SMTP, no por aquí.
     */
    public function up(): void
    {
        Schema::create('notificacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo', 50);          // STOCK_BAJO, SOLICITUD_REPUESTO, PEDIDO_POR_APROBAR, ...
            $table->string('mensaje');
            $table->string('recurso')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('fecha')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion');
    }
};
