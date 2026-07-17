<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('correo')->nullable();
            $table->enum('accion', ['LOGIN_OK', 'LOGIN_FAIL', 'ACCESO_RECURSO']);
            $table->string('recurso')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agente_usuario')->nullable();
            $table->timestamp('fecha')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('usuario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
