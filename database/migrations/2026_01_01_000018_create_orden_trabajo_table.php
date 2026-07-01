<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_trabajo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('moto_id');
            $table->timestamp('fecha_ingreso')->useCurrent();
            $table->text('descripcion_problema');
            $table->text('diagnostico')->nullable();
            $table->timestamp('fecha_diagnostico')->nullable();
            $table->decimal('costo_estimado_mano_obra', 10, 2)->nullable();
            $table->decimal('costo_estimado_repuestos', 10, 2)->nullable();
            $table->boolean('presupuesto_aprobado')->default(false);
            $table->decimal('costo_mano_obra', 10, 2)->nullable();        // real, al facturar
            $table->unsignedBigInteger('venta_id')->nullable();           // factura generada
            $table->enum('estado', ['RECIBIDA', 'DIAGNOSTICADA', 'EN_REPARACION', 'TERMINADA', 'ENTREGADA', 'CANCELADA'])
                  ->default('RECIBIDA');
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('cliente')->onDelete('restrict');
            $table->foreign('moto_id')->references('id')->on('moto')->onDelete('restrict');
            $table->foreign('venta_id')->references('id')->on('venta')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_trabajo');
    }
};
