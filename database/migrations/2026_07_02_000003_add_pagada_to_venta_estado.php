<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el estado PAGADA a venta.estado.
 * En PostgreSQL, Laravel ->enum() crea un varchar + CHECK (no un tipo ENUM nativo),
 * así que hay que recrear la restricción venta_estado_check.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE venta DROP CONSTRAINT venta_estado_check');
        DB::statement("ALTER TABLE venta ADD CONSTRAINT venta_estado_check CHECK (estado::text = ANY (ARRAY['COMPLETADA','PENDIENTE','PAGADA','ANULADA']::text[]))");
    }

    public function down(): void
    {
        DB::statement("UPDATE venta SET estado = 'COMPLETADA' WHERE estado = 'PAGADA'");
        DB::statement('ALTER TABLE venta DROP CONSTRAINT venta_estado_check');
        DB::statement("ALTER TABLE venta ADD CONSTRAINT venta_estado_check CHECK (estado::text = ANY (ARRAY['COMPLETADA','PENDIENTE','ANULADA']::text[]))");
    }
};
