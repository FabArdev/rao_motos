<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->enum('tecnica_inventario', ['PERMANENTE', 'PERIODICO'])->default('PERMANENTE');
            $table->enum('tecnica_costo', ['PEPS', 'UEPS', 'PROMEDIO'])->default('PROMEDIO');
            $table->timestamp('fecha_actualizacion')->useCurrent();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
