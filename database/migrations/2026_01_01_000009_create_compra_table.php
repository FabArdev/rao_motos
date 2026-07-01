<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('total', 12, 2)->default(0);     // calculado por el servidor desde el detalle
            $table->enum('estado', ['PENDIENTE', 'RECIBIDA', 'ANULADA'])->default('PENDIENTE');
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedor')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra');
    }
};
