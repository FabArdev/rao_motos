<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_cuota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credito_id');
            $table->integer('numero_cuota');
            $table->decimal('monto_cuota', 10, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->decimal('mora', 10, 2)->default(0);
            $table->enum('estado', ['PENDIENTE', 'PAGADO', 'VENCIDO'])->default('PENDIENTE');

            // PagoFácil (pago de cuota por QR)
            $table->unsignedBigInteger('metodo_pago_id')->nullable();
            $table->string('pago_facil_id_transaccion', 100)->nullable();
            $table->string('pago_facil_numero_pago', 120)->nullable();
            $table->text('pago_facil_imagen_qr')->nullable();
            $table->string('pago_facil_estado', 50)->nullable();
            $table->text('pago_facil_respuesta_cruda')->nullable();

            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->foreign('credito_id')->references('id')->on('credito')->onDelete('restrict');
            $table->foreign('metodo_pago_id')->references('id')->on('metodo_pago')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_cuota');
    }
};
