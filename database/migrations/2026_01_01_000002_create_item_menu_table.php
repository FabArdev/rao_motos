<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_menu', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta');
            $table->string('ruta_laravel');
            $table->string('icono')->nullable();
            $table->integer('orden')->default(0);
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->foreign('rol_id')->references('id')->on('rol')->onDelete('cascade');
            $table->foreign('padre_id')->references('id')->on('item_menu')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_menu');
    }
};
