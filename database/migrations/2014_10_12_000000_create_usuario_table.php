<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('ci')->unique();           // carnet de identidad personal
            $table->string('telefono', 15);
            $table->string('direccion')->nullable();

            // Correo (nullable solo para clientes)
            $table->string('correo')->nullable()->unique();
            $table->timestamp('correo_verificado_en')->nullable();

            // Acceso
            $table->string('password');
            $table->string('token_recordar', 100)->nullable();

            // Otros campos del sistema Jetstream
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();

            // Estado del usuario (activo/inactivo)
            $table->boolean('estado')->default(true);

            // Fecha de nacimiento opcional
            $table->date('fecha_nacimiento')->nullable();

            // Rol (un rol por usuario)
            $table->unsignedBigInteger('rol_id')->nullable();
            $table->foreign('rol_id')->references('id')->on('rol')->onDelete('restrict');

            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
