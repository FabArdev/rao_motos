<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->timestamp('fecha')->useCurrent();
            $table->enum('estado', ['SOLICITADO', 'APROBADO', 'RECHAZADO', 'EN_PROCESO', 'DESPACHADO', 'ANULADO'])
                  ->default('SOLICITADO');
            $table->string('motivo_rechazo')->nullable();
            $table->unsignedBigInteger('venta_id')->nullable();   // venta generada al aprobar
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('cliente')->onDelete('restrict');
            $table->foreign('venta_id')->references('id')->on('venta')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido');
    }
};
