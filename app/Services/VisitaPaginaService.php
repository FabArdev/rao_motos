<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  VisitaPaginaService — Contador de visitas por página
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cuenta cuántas veces se visita cada página del sitio. El total
 *  se muestra en el pie de página (footer). Ignora las rutas
 *  técnicas (imágenes, webhooks, etc.) para no ensuciar el conteo.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: Service (App\Services). Requisito transversal REQ7.
 *  - Modelo: VisitaPagina (columnas ruta, contador).
 *  - debeContabilizar(): descarta rutas que empiezan por webhook,
 *    pagofacil, livewire, build o storage.
 *  - registrarVisita(): crea la fila o incrementa el contador.
 *  - total(): suma todos los contadores.
 *  - Se dispara desde el middleware RegistrarVisitasPagina.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Services;

use App\Models\VisitaPagina;

class VisitaPaginaService
{

    private array $excluidas = ['webhook', 'pagofacil', 'livewire', 'build', 'storage'];

    public function debeContabilizar(string $ruta): bool
    {
        foreach ($this->excluidas as $pref) {
            if (str_starts_with($ruta, $pref)) {
                return false;
            }
        }

        return true;
    }

    public function registrarVisita(string $ruta): void
    {

        $existe = VisitaPagina::where('ruta', $ruta)->exists();
        if ($existe) {
            VisitaPagina::where('ruta', $ruta)->increment('contador');
        } else {
            VisitaPagina::create(['ruta' => $ruta, 'contador' => 1]);
        }
    }

    public function total(): int
    {
        return (int) VisitaPagina::sum('contador');
    }
}
