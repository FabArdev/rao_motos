<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id')->unique();
            $table->integer('numero_cuotas');                    // >= 2 (validado en negocio)
            $table->decimal('tasa_interes', 5, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->enum('estado', ['VIGENTE', 'PAGADO', 'MOROSO'])->default('VIGENTE');
            $table->timestamps();

            $table->foreign('venta_id')->references('id')->on('venta')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credito');
    }
};
