<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->string('numero_venta', 30)->unique()->nullable();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('vendedor_id')->nullable();   // users.id (rol vendedor/admin)
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('monto_total', 12, 2);                   // calculado por el servidor desde el detalle
            $table->enum('tipo_venta', ['CONTADO', 'CREDITO']);
            $table->enum('metodo_pago', ['EFECTIVO', 'QR']);
            $table->enum('estado', ['COMPLETADA', 'PENDIENTE', 'ANULADA'])->default('PENDIENTE');

            // PagoFácil (pago por QR)
            $table->string('pago_facil_id_transaccion', 100)->nullable();
            $table->string('pago_facil_numero_pago', 120)->nullable();
            $table->text('pago_facil_imagen_qr')->nullable();
            $table->string('pago_facil_estado', 50)->nullable();     // pending, completed, failed
            $table->text('pago_facil_respuesta_cruda')->nullable();

            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->foreign('cliente_id')->references('id')->on('cliente')->onDelete('restrict');
            $table->foreign('vendedor_id')->references('id')->on('usuario')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};
