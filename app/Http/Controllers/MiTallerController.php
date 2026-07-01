<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MiTallerController extends Controller
{
    public function index(Request $request)
    {
        $ordenes = OrdenTrabajo::with('moto')
            ->where('cliente_id', $request->user()->id)
            ->latest('fecha_ingreso')
            ->paginate(12);

        return Inertia::render('MiTaller/Index', ['ordenes' => $ordenes]);
    }

    public function show(Request $request, OrdenTrabajo $orden)
    {
        $this->autorizar($request, $orden);
        $orden->load(['moto', 'detalles.producto', 'venta.credito']);

        return Inertia::render('MiTaller/Show', ['orden' => $orden]);
    }

    /** El cliente aprueba el presupuesto → EN_REPARACION (RN6: sin aprobación no empieza). */
    public function aprobarPresupuesto(Request $request, OrdenTrabajo $orden)
    {
        $this->autorizar($request, $orden);

        if ($orden->estado !== 'DIAGNOSTICADA') {
            return back()->with('error', 'La orden no tiene un presupuesto por aprobar.');
        }

        $orden->update(['presupuesto_aprobado' => true, 'estado' => 'EN_REPARACION']);

        return back()->with('success', 'Presupuesto aprobado. El mecánico iniciará la reparación.');
    }

    public function rechazarPresupuesto(Request $request, OrdenTrabajo $orden)
    {
        $this->autorizar($request, $orden);

        if ($orden->estado !== 'DIAGNOSTICADA') {
            return back()->with('error', 'La orden no tiene un presupuesto por aprobar.');
        }

        $orden->update(['presupuesto_aprobado' => false, 'estado' => 'CANCELADA']);

        return back()->with('success', 'Presupuesto rechazado. La orden fue cancelada.');
    }

    private function autorizar(Request $request, OrdenTrabajo $orden): void
    {
        if ($orden->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }
    }
}
