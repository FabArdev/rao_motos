<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subtabla 1:1 de users (herencia por rol). Solo para usuarios con rol cliente.
     * id = users.id. Único dato propio: nit_ci (NIT de facturación, distinto del ci personal).
     */
    public function up(): void
    {
        Schema::create('cliente', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('nit_ci', 20)->nullable();
            $table->timestamps();

            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
