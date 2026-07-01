<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repuestos solicitados por el mecánico para una orden.
     * El almacenero aprueba (descuenta inventario una sola vez) o rechaza.
     */
    public function up(): void
    {
        Schema::create('detalle_orden', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->enum('estado', ['SOLICITADO', 'APROBADO', 'RECHAZADO', 'ENTREGADO'])->default('SOLICITADO');
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->foreign('orden_trabajo_id')->references('id')->on('orden_trabajo')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_orden');
    }
};
