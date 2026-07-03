<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('producto')->cascadeOnDelete();
            $table->string('ruta', 255);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagen');
    }
};
