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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('ci')->unique();           // carnet de identidad personal
            $table->string('telefono', 15);
            $table->string('direccion')->nullable();

            // Email (nullable solo para clientes)
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Acceso
            $table->string('password');
            $table->rememberToken();

            // Otros campos del sistema Jetstream
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();

            // Estado del usuario (activo/inactivo)
            $table->boolean('estado')->default(true);

            // Fecha de nacimiento opcional
            $table->date('fecha_nacimiento')->nullable();

            // Rol (un rol por usuario)
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
