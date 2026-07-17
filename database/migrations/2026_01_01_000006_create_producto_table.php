<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta_base', 10, 2);                 // minorista
            $table->decimal('precio_mayorista', 10, 2);                  // por volumen
            $table->integer('cantidad_minima_mayorista')->default(1);    // umbral mayoreo POR PRODUCTO
            $table->string('foto_url', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
