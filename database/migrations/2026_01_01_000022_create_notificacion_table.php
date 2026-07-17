<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificaciones in-app (badge en el navbar). Complementadas por email/SMTP (planificado).
     */
    public function up(): void
    {
        Schema::create('notificacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo', 50);          // STOCK_BAJO, PEDIDO_POR_APROBAR, VENTA_PAGADA, PEDIDO_APROBADO, PEDIDO_RECHAZADO, PEDIDO_DESPACHADO, MORA
            $table->string('mensaje');
            $table->string('recurso')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('fecha')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion');
    }
};
