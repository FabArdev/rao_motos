<?php

namespace App\Services;

use App\Models\VisitaPagina;

/**
 * Contador de visitas por ruta (REQ7). El total se muestra en el footer.
 */
class VisitaPaginaService
{
    /** Rutas que NO se contabilizan (assets, callbacks, etc.). */
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
        // upsert atómico: crea la fila o incrementa el contador.
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
