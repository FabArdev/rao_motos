<?php

namespace App\Services;

use App\Models\PageVisit;

/**
 * Contador de visitas por ruta (REQ7). El total se muestra en el footer.
 */
class PageVisitService
{
    /** Rutas que NO se contabilizan (assets, callbacks, etc.). */
    private array $excluidas = ['webhook', 'pagofacil', 'notificaciones', 'livewire', 'build', 'storage'];

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
        $existe = PageVisit::where('ruta', $ruta)->exists();
        if ($existe) {
            PageVisit::where('ruta', $ruta)->increment('contador');
        } else {
            PageVisit::create(['ruta' => $ruta, 'contador' => 1]);
        }
    }

    public function total(): int
    {
        return (int) PageVisit::sum('contador');
    }
}
