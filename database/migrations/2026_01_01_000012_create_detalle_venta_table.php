<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una línea es un repuesto (producto_id) o un servicio/mano de obra (descripcion, producto_id NULL).
     */
    public function up(): void
    {
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('producto_id')->nullable();   // NULL para servicios / mano de obra
            $table->string('descripcion')->nullable();               // usado cuando no es producto
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->timestamps();

            $table->foreign('venta_id')->references('id')->on('venta')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};
